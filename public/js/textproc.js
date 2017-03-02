function modalPopup() {
    var selection = window.getSelection();
    var parent1 = selection.getRangeAt(0).startContainer.parentNode;
    var parent2 = selection.getRangeAt(0).endContainer.parentNode;
    if (this.className == 'transl-word') {
        tWId1 = parent1.getAttribute('data-transl-word-id');
        tWId2 = parent2.getAttribute('data-transl-word-id');
        wId1 = crossrefs.transl[tWId1];
        wId2 = crossrefs.transl[tWId2];
    } else {
        wId1 = parent1.getAttribute('data-word-id');
        wId2 = parent2.getAttribute('data-word-id');
    }

    if (wId1 != wId2) {
        // TODO: work with selecting multiple words thru sibling elements
    } else {
        // TODO: entry point to current code.
    }

    if (document.getElementById('modal1')) {
        mod1 = document.getElementById('modal1');
        modContentText = document.getElementsByClassName('wordTypeDiv');
        modContentText[0].style.display = 'block';
        modContentSubSel = document.getElementById('modsubselect');
        modContentSubSel.style.display = 'block';
        addTranslLinkBt = document.getElementById('add-transl-link-btn');
        addTranslLinkBt.style.display = 'none';
        translationLinkBt = document.getElementById('transl-link-btn');
        translationLinkBt.style.display = 'block';
        mod1.style.display = 'block';
    } else {
        var mod1 = document.createElement('div');
        mod1.id = "modal1";
        mod1.className = "modal";
        var modContent = document.createElement('div');
        modContent.className = "modal-content";
        var closeSpan = document.createElement('span');
        closeSpan.className = "close";
        closeSpan.innerText = "\u2297";
        closeSpan.onclick = function() {
            mod1.style.display = "none";
            var wordColl = document.getElementsByClassName('word');
            var translWordColl = document.getElementsByClassName('transl-word');
            for ($1=0, $size = wordColl.length; $i<$size; $i++) {
                wordColl[$i].onmouseup = modalPopup;
            }
            for ($1=0, $size = translWordColl.length; $i<$size; $i++) {
                translWordColl[$i].onmouseup = modalPopup;
            }
        }
        var translationLinkBt = document.createElement('button');
        translationLinkBt.id = 'transl-link-btn';
        translationLinkBt.innerText = 'Link orig. and transl. words';
        var addTranslLinkBt = document.createElement('button');
        addTranslLinkBt.id = 'add-transl-link-btn';
        addTranslLinkBt.style.display = 'none';
        addTranslLinkBt.onclick = function () {
            var params = this.value.split(",");
            $.ajax({
                url:'/text/addTranslationAjax',
                type: 'POST',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                headers: {
                    Accept : "application/json; charset=utf-8",
                },
                data: {'text_id': window.location.href.split('/').pop(),
                    'orig_word_id': params[0].split(": ")[1],
                    'transl_word_id': params[1].split(": ")[1]},
                success: function (data, status) {
                    var wordColl = document.getElementsByClassName('word');
                    var translWordColl = document.getElementsByClassName('transl-word');
                    for ($1=0, $size = wordColl.length; $i<$size; $i++) {
                        wordColl[$i].onmouseup = modalPopup;
                    }
                    for ($1=0, $size = translWordColl.length; $i<$size; $i++) {
                        translWordColl[$i].onmouseup = modalPopup;
                    }
                    mod1 = document.getElementById('modal1');
                    mod1.style.display = 'none';

                    return data;
                },
                error: function(xhr, desc, err) {
                    console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
            })
        }
        translationLinkBt.onclick = function() {
            var selection = window.getSelection();
            var parent1 = selection.getRangeAt(0).startContainer.parentNode;
            mod1.style.display = "none";
            if (parent1.className == 'transl-word') {
                var translWordColl = document.getElementsByClassName('word');
                for ($i = 1, $size = translWordColl.length; $i<$size; $i++) {
                    translWordColl[$i].onclick = function () {
                        var subsel = document.getElementById('modsubselect');
                        subsel.style.display = 'none';
                        modContentText.style.display = 'none';
                        mod1.style.display = 'block';
                        var transId = parent1.getAttribute('data-transl-word-id');
                        var transWord = parent1.textContent;
                        var origId = this.dataset.wordId;
                        var origWord = this.textContent;
                        translationLinkBt.style.display = 'none';
                        addTranslLinkBt.innerText = 'Original: '+ origWord +
                            ' => Translation: '+ transWord;
                        addTranslLinkBt.value = origId + "," + transId;
                        addTranslLinkBt.style.display = 'block';
                    }
                }
            } else {
                var translWordColl = document.getElementsByClassName('transl-word');
                for ($i = 1, $size = translWordColl.length; $i<$size; $i++) {
                    translWordColl[$i].onclick = function () {
                        modContentText.style.display = 'none';
                        var subsel = document.getElementById('modsubselect');
                        subsel.style.display = 'none';
                        mod1.style.display = 'block';
                        var transId = this.dataset.translWordId;
                        var transWord = this.textContent;
                        var origId = parent1.getAttribute('data-word-id');
                        var origWord = parent1.textContent;
                        translationLinkBt.style.display = 'none';
                        addTranslLinkBt.innerText = 'Original: ' + origWord + ' => Translation: '
                            + transWord;
                        addTranslLinkBt.value = "orig: " + origId + ", transl: " + transId;
                        addTranslLinkBt.style.display = 'block';
                    }
                }
            }
        }
        var modContentText = document.createElement('div');
        modContentText.innerHTML = "Define word type: ";
        modContentText.className = "wordTypeDiv";
        modContent.appendChild(closeSpan);
        modContent.appendChild(translationLinkBt);
        modContent.appendChild(addTranslLinkBt);
        modContent.appendChild(modContentText);
        var newSelect = document.createElement('select');
        newSelect.id = "combobox";
        newSelect.addEventListener('change', addSubSelect);
        getoptions(newSelect, 'word_type', true);
        modContentText.appendChild(newSelect);
        mod1.appendChild(modContent);
        mod1.style.display = "block";
        document.body.appendChild(mod1);
    }
}

function wordHighlightOn() {
    this.style.backgroundColor = "yellow";
    if (this.className == 'transl-word') {
        crossWordId = crossrefs.transl[this.dataset.translWordId];
        if (crossWordId) {
            var word = $("[data-word-id="+crossWordId+"]");
            word[0].style.backgroundColor = "yellow";
        }
    }
    if (this.className == 'word') {
        crossWordId = crossrefs.orig[this.dataset.wordId];
        if (crossWordId) {
            var word = $("[data-transl-word-id="+crossWordId+"]");
            word[0].style.backgroundColor = "yellow";
        }
    }
}

function wordHighlightOut() {
    this.style.backgroundColor = "transparent";
    if (this.className == 'transl-word') {
        crossWordId = crossrefs.transl[this.dataset.translWordId];
        if (crossWordId) {
            var word = $("[data-word-id="+crossWordId+"]");
            word[0].style.backgroundColor = "transparent";
        }
    }
    if (this.className == 'word') {
        crossWordId = crossrefs.orig[this.dataset.wordId];
        if (crossWordId) {
            var word = $("[data-transl-word-id="+crossWordId+"]");
            word[0].style.backgroundColor = "transparent";
        }
    }
}

function addSubSelect(getOptionsValue=null) {
    if (typeof getOptionsValue != 'string' && typeof getOptionsValue != 'number') {
        getOptionsValue = this.selectedOptions[0].value;
    }
    if (modsubsel = document.getElementById('modsubselect')) {
        subSelDiv = modsubsel.children['combobox2'];
        while (subSelDiv.options.length > 0) {
            subSelDiv.remove(0);
        }
        getoptions(subSelDiv, this.selectedOptions[0].value)
    } else {
        var subSelDiv = document.createElement("div");
        subSelDiv.id = "modsubselect";
        subSelDiv.className = "ui-widget typeOptionDiv";
        subSelDiv.innerText = "Select word type option: ";
        var newSubSelect = document.createElement('select');
        newSubSelect.id = "combobox2";
        subSelDiv.appendChild(newSubSelect);
        getoptions(newSubSelect, getOptionsValue);
        document.getElementById('modal1').children[0].appendChild(subSelDiv);
        var button = document.createElement('button');
        button.innerHTML = 'Add text ref.';
        button.addEventListener('click', insertReference);
        subSelDiv.appendChild(button);
    }
}

function insertReference() {
    var siblings = this.parentNode.parentNode.children;
    for(var i1 = 0, size1 = siblings.length; i1<size1; i1++) {
        var sibling = siblings[i1];
        var classList = sibling.classList;
        for(var i = 0, size = classList.length; i < size ; i++) {
            var className = classList[i];
            switch (className) {
                case 'typeOptionDiv':
                    var typeOption = sibling.children[0].selectedOptions[0].value;
                    break;
                case 'wordTypeDiv':
                    var wordType = sibling.children[0].selectedOptions[0].value;
                    break;
            }
        }
    }
    var options = $.ajax({
        url:'/text/addOptionAjax',
        type: 'POST',
        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
        headers: {
            Accept : "application/json; charset=utf-8",
        },
        data: {'word_id': wId1, 'word_type': wordType, 'type_option': typeOption},
        success: function (data, status) {
            mod1 = document.getElementById('modal1');
            mod1.style.display = 'none';
            return data;
        },
        error: function(xhr, desc, err) {
            console.log(xhr);
            console.log("Details: " + desc + "\nError:" + err);
        }
    })
}


function addoption(selectbox, value, text) {
    var option = new Option(text, value);
    selectbox.append(option);
}

function getoptions(selectbox, optionType, subselect=false) {
    var options = $.ajax({
        url:'/text/ajax',
        type: 'POST',
        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
        headers: {
            Accept : "application/json; charset=utf-8",
        },
        data: {'option_type': optionType},
        success: function (data, status) {
            for ($i =0, $size = data.length; $i < $size; $i++) {
                var option = data[$i];
                addoption(selectbox, option[0], option[1]);
            }
            selectbox.options[0].selected = true;
            if (subselect == true) {
                addSubSelect(selectbox.selectedOptions[0].value);
            }
            return data;
        },
        error: function(xhr, desc, err) {
            console.log(xhr);
            console.log("Details: " + desc + "\nError:" + err);
        }
    });
}

$( function() {
    $('.transl-word').hover(wordHighlightOn, wordHighlightOut);
    $('.word').hover(wordHighlightOn, wordHighlightOut);
} );