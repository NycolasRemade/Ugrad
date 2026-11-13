function isLetter(char) {
    return char = char.charCodeAt(0), (char >= 97 && char <= 122) || (char >= 65 && char <= 90);
}

function lexer(str) {

    const tokens = [];
    if (!str) return tokens;

    for (let i = 0, len = str.length; i < len; ++i) {

        if (str[i] === '<' && str[i+1] === '|') {
            ++i;
            const tagTk = { type: 'tag', value: '' };
            while (str[i+1] === ' ' || str[i+1] === '\n') ++i;
            while (str[++i] && str[i] !== ' ' && str[i] !== '\n' && !(str[i] === '#' && str[i+1] === '>')) {
                tagTk.value += str[i];
            }
            if (str[i] !== ' ') --i;
            tokens.push(tagTk);
            continue;
        }

        if (str[i] === '<' && str[i+1] === '#') {
            ++i;
            const tagTk = { type: 'tag', value: 'bloco' };
            while (str[i+1] === ' ' || str[i+1] === '\n') ++i;
            while (str[++i] && str[i] !== ' ' && str[i] !== '\n' && !(str[i] === '#' && str[i+1] === '>')) {
                tagTk.value += str[i];
            }
            if (str[i] !== ' ') --i;
            tokens.push(tagTk);
            continue;
        }

        if (str[i] === '<' && str[i+1] === '¨') {
            ++i;
            const tagTk = { type: 'tag', value: 'blocoP' };
            while (str[i+1] === ' ' || str[i+1] === '\n') ++i;
            while (str[++i] && str[i] !== ' ' && str[i] !== '\n' && !(str[i] === '#' && str[i+1] === '>')) {
                tagTk.value += str[i];
            }
            if (str[i] !== ' ') --i;
            tokens.push(tagTk);
            continue;
        }

        if (str[i] === '<' && str[i+1] === ';') {
            ++i;
            const tagTk = { type: 'tag', value: 'blocoM' };
            while (str[i+1] === ' ' || str[i+1] === '\n') ++i;
            while (str[++i] && str[i] !== ' ' && str[i] !== '\n' && !(str[i] === '#' && str[i+1] === '>')) {
                tagTk.value += str[i];
            }
            if (str[i] !== ' ') --i;
            tokens.push(tagTk);
            continue;
        }

        if (str[i] === '<' && str[i+1] === '/') {
            ++i;
            const tagTk = { type: 'tag', value: 'blocoG' };
            while (str[i+1] === ' ' || str[i+1] === '\n') ++i;
            while (str[++i] && str[i] !== ' ' && str[i] !== '\n' && !(str[i] === '#' && str[i+1] === '>')) {
                tagTk.value += str[i];
            }
            if (str[i] !== ' ') --i;
            tokens.push(tagTk);
            continue;
        }




        if (str[i] === '#' && str[i+1] === '>') {
            ++i;
            tokens.push({ type: 'closetag', value: '' });
            if (str[i+1] === ' ' || str[i+1] === '\n') ++i;
            continue;
        }

        if (str[i] === "'") {
            const strTk = { type: 'string', value: '' };
            while (str[++i] && str[i] !== "'") {
                if (str[i] === '\\') ++i;
                strTk.value += str[i];
            }
            tokens.push(strTk);
            continue;
        }

        if (str[i] === '#' && (!str[i-1] || str[i-1] === '\n')) {
            if (str[i+1] === '#' && str[i+2] === '#') {
                i += 2;
                tokens.push({ type: 'tag', value: 'h1' });
            } 

            else if(str[i+1] === '#'){
                ++i;
                tokens.push({ type: 'tag', value: 'h2' });
            }
            
            else {
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
    return tokens;
}

const parser = (function(){

    let _tokens;
    let _i = 0;
    let _fragment;
    function _parse_token() {
        switch (_tokens[_i].type) {
            case "tag":
                switch (_tokens[_i].value) {
                    case "h1": 
                    case "h2":                     
                    case "h3":{
                        const title = document.createElement("span");
                        title.className = (_tokens[_i].value === "h1") ? "historia-titulo" : ((_tokens[_i].value === "h2") ? "historia-subtitulo" : "historia-textogrande");
                        while (_tokens[++_i].type !== "closetag") {
                            title.innerText += _tokens[_i].value + '\n';
                        }
                        --_i;
                        _fragment.appendChild(title);
                        break;
                    }
                    case "img": {
                        ++_i;
                        if (_tokens[_i].type === "string") {
                            const img = document.createElement("div");
                            img.className = "historia-imagem";
                            img.style.backgroundImage = "url('" + _tokens[_i].value + "')";
                            _fragment.appendChild(img);
                        }
                        if (_tokens[_i+1].type === "closetag") ++_i;
                        break;
                    }
                    case "bloco":
                    case "blocoP" : 
                    case "blocoM" : 
                    case "blocoG" : {
                        const bloco = document.createElement("div");
                        bloco.className = (_tokens[_i].value === "bloco") ? "box long" : ((_tokens[_i].value === "blocoP") ? "box short" : ((_tokens[_i].value === "blocoM") ? "poly medio" : "poly rebarba"));
                        const previousFragment = _fragment;
                        _fragment = bloco;
                        while (_tokens[++_i] && _tokens[_i].type !== "closetag") {
                            _parse_token();
                        }
                        --_i;
                        _fragment = previousFragment;
                        _fragment.appendChild(bloco);
                    }
                    case "br": {
                        _fragment.appendChild(document.createElement("br"));
                        if (_tokens[_i+1]?.type === "closetag") ++_i;
                        break;
                    }
                    default: {
                        _fragment.appendChild(
                            document.createTextNode('<#' + _tokens[_i].value + '#>')
                        );
                        break;
                    }
                }
                break;
            case "string": {
                const node = document.createTextNode(_tokens[_i].value);
                _fragment.appendChild(node);
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
        return _fragment;
    };

})();