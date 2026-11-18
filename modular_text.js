function isLetterOrNumber(char) {
    return char = char.charCodeAt(0), (char >= 97 && char <= 122) || (char >= 65 && char <= 90) || (char >= 48 && char <= 57);
}

const lexer = (function() {

    const _blockTags = {
        '|': 'bloco',
        '¨': 'blocoP',
        ';': 'blocoM',
        '/': 'blocoG'
    };

    // const _stringStyles = {
    //     "'": '',
    //     '*': 'italic',
    //     '_': 'bold',
    //     '§': 'meringue'
    // };
    
    return function lexer(str) {

        const tokens = [];
        if (!str) return tokens;

        for (let i = 0, len = str.length; i < len; ++i) {

            if (str[i] === '<' && str[i+1] === '#') {
                ++i;
                const tagTk = { type: 'tag', value: '' };
                while (str[i+1] === ' ' || str[i+1] === '\n') ++i;
                while (str[++i] && isLetterOrNumber(str[i])) {
                    tagTk.value += str[i];
                }
                if (str[i] !== ' ') --i;
                tokens.push(tagTk);
                continue;
            }

            if (str[i] === '<' && typeof _blockTags[str[i+1]] === "string") {
                ++i;
                const tagTk = { type: 'tag', value: _blockTags[str[i]] };
                while (str[i+1] === ' ' || str[i+1] === '\n') ++i;
                tokens.push(tagTk);
                continue;
            }

            if (str[i] === '#' && str[i+1] === '>') {
                ++i;
                tokens.push({ type: 'closetag', value: '' });
                if (str[i+1] === ' ') ++i;
                continue;
            }

            // if (typeof _stringStyles[str[i]] === "string") {
            //     const strHolder = _stringStyles[str[i]];
            //     const strTk = { type: 'string', value: "", style: strHolder };
            //     while (str[++i] && (_stringStyles[str[i]] !== strHolder)) { 
            //         if (str[i] === '\\') ++i;
            //         strTk.value += str[i];
            //     }
            //     tokens.push(strTk);
            //     console.log(strTk.value +" debug");
            //     continue;
            // }


            if (str[i] === '#' && (!str[i-1] || str[i-1] === '\n')) {

                if (str[i+1] === '#' && str[i+2] === '#') {
                    i += 2;
                    tokens.push({ type: 'tag', value: 'h1' });
                } else if(str[i+1] === '#'){
                    ++i;
                    tokens.push({ type: 'tag', value: 'h2' });
                } else {
                    tokens.push({ type: 'tag', value: 'h3' });
                }

                if (str[i+1] === ' ') ++i;
                const strTk = { type: 'string', value: '' };
                while (str[++i] && str[i] !== '\n') {
                    if (str[i] === '\\') ++i;
                    strTk.value += str[i];
                }
                tokens.push(strTk);
                tokens.push({ type: 'closetag', value: '' });
                continue;
            }

            if (str[i] === '\n') {
                tokens.push({ type: 'br', value: '' });
                continue;
            }

            const tk = { type: 'string', value: '' };
            while (str[i] && str[i] !== '\n' && !(str[i] === '<' && str[i+1] === '#') && !(str[i] === '#' && str[i+1] === '>')) {
                if (str[i] === '\\') ++i;
                tk.value += str[i];
                ++i;
            }
            if (str[i] !== ' ') --i;
            tokens.push(tk);

        }
        console.log(tokens);
        return tokens;
    }
})();

const parser = (function(){

    let _tokens;
    let _i = 0;
    let _fragment;
    function _parse_token() {

        const _tagClasses = {
            'h1': 'historia-titulo',
            'h2': 'historia-subtitulo',
            'h3': 'historia-textogrande',

            'bloco': 'box long',
            'blocoP': 'box short',
            'blocoM': 'poly medio',
            'blocoG': 'poly rebarba',

            'b': 'bold',
            'i': 'italic',
            'me': 'meringue',
        };

        switch (_tokens[_i].type) {
            case "tag":
                switch (_tokens[_i].value) {
                    case "img": {
                        ++_i;
                        if (_tokens[_i].type === "string") {
                            const img = document.createElement("img");
                            img.className = "historia-imagem";
                            img.style.backgroundImage = "url(" + _tokens[_i].value + ")";
                            _fragment.appendChild(img);
                        }
                        if (_tokens[_i+1]?.type === "closetag") ++_i;
                        break;
                    }
                    case "h1": 
                    case "h2":                     
                    case "h3":
                    case "b":
                    case "i":
                    case "me": {
                        const title = document.createElement("span");
                        title.className = _tagClasses[_tokens[_i].value]; 
                        const previousFragment = _fragment;
                        _fragment = title;
                        while (_tokens[++_i] && _tokens[_i].type !== "closetag") {
                            _parse_token();
                        }
                        _fragment = previousFragment;
                        _fragment.appendChild(title);
                        break;
                    }
                    case "bloco":
                    case "blocoP": 
                    case "blocoM": 
                    case "blocoG": {
                        const bloco = document.createElement("div");
                        bloco.className = _tagClasses[_tokens[_i].value];
                        const previousFragment = _fragment;
                        _fragment = bloco;
                        while (_tokens[++_i] && _tokens[_i].type !== "closetag") {
                            _parse_token();
                        }
                        _fragment = previousFragment;
                        _fragment.appendChild(bloco);
                        break;
                    }
                    case "br": {
                        _fragment.appendChild(document.createElement("br"));
                        if (_tokens[_i+1]?.type === "closetag") ++_i;
                        break;
                    }
                    default: {
                        _fragment.appendChild(
                            document.createTextNode(_tokens[_i].value)
                        );
                        break;
                    }
                }
                break;
            case "string": {
                // switch (_tokens[_i].style) {

                //     case "italic":
                //     case "bold":
                //     case "meringue": {
                //         const text = document.createElement("span");                   //Todo esse código aparentemente não funciona, pois os casos nunca acontecem, 
                //         text.className = _tokens[_i].style;                            // e se forçar eles a acontecerem, resulta em um erro que diz que o valor é 'undefined', mesmo que claramente ele não é.
                //         text.innerText = _tokens[_i].value;
                //         _fragment.appendChild(text);
                //         console.log(_tokens[_i].value);
                //         break;
                //     }

                //     default: {
                        const node = document.createTextNode(_tokens[_i].value);
                        _fragment.appendChild(node);
                        console.log(_tokens[_i].value + " default");
                        break;
                //     }
                // }
                break;
            }
            case "closetag": {
                break;
            }
            case "br": {
                _fragment.appendChild(document.createElement("br"));
                break;
            }
            default: {
                _fragment.appendChild(
                    document.createTextNode(_tokens[_i].value)
                );
                break;
            }
        }
    }

    return function parser(tokens) {

        if (!tokens) return document.createTextNode("Conteúdo da história não informado.");

        _tokens = tokens;
        _fragment = document.createDocumentFragment();
        for (_i = 0; _i < tokens.length; ++_i) {
            _parse_token();
        }
        console.log(_fragment.children);
        return _fragment;
    };

})();

//Edição de história user-friendly

    var divsaida = document.getElementById('saida-historia-projeto')
    function userFriendlyEdit(divsaida){
        elementos = divsaida;
        console.log(elementos)
    }

    userFriendlyEdit(divsaida)