function isLetter(char) {
    return char = char.charCodeAt(0), (char >= 97 && char <= 122) || (char >= 65 && char <= 90);
}

function lexer(str) {

    const tokens = [];
    for (let i = 0, len = str.length; i < len; ++i) {

        if (str[i] === '<' && str[i+1] === '¨') {
            ++i;
            const tagTk = { type: 'tag', value: '' };
            while (str[++i] && str[i] !== ' ' && !(str[i] === '¨' && str[i+1] === '>')) {
                tagTk.value += str[i];
            }
            if (str[i] !== ' ') --i;
            tokens.push(tagTk);
            continue;
        }

        if (str[i] === '¨' && str[i+1] === '>') {
            ++i;
            tokens.push({ type: 'closetag', value: '' });
            continue;
        }

        if (str[i] === '"') {
            const strTk = { type: 'string', value: '' };
            while (str[++i] && str[i] !== '"') {
                if (str[i] === '\\') ++i;
                strTk.value += str[i];
            }
            tokens.push(strTk);
            continue;
        }

        const tk = { type: 'string', value: '' };
        while (str[i] && !(str[i] === '<' && str[i+1] === '¨') && !(str[i] === '¨' && str[i+1] !== '>')) {
            if (str[i] === '\\') {
                ++i;
            }
            tk.value += str[i];
            ++i;
        }
        --i;
        tokens.push(tk);

    }
    return tokens;
}

function parser(tokens) {

    const fragment = document.createDocumentFragment();
    for (let i = 0; i < tokens.length; ++i) {
        switch (tokens[i].type) {
            case "tag":
                switch (tokens[i].value) {
                    case "t": 
                    case "sub": {
                        const title = document.createElement("span");
                        title.className = (tokens[i].value === "t") ? "historia-titulo" : "historia-subtitulo";
                        while (tokens[++i].type !== "closetag") {
                            title.innerText += tokens[i].value + '\n';
                        }
                        --i;
                        break;
                    }
                    case "img": {
                        ++i;
                        if (tokens[i].type === "string") {
                            const img = document.createElement("div");
                            img.className = "historia-imagem";
                            img.style.backgroundImage = "url('" + tokens[i].value + "')";
                            fragment.appendChild(img);
                        }
                        if (tokens[i+1].type === "closetag") ++i;
                        break;
                    }
                    case "br": {
                        fragment.appendChild(document.createElement("br"));
                        break;
                    }
                    default: {
                        fragment.appendChild(
                            document.createTextNode('<' + tokens[i].value + '>')
                        );
                    }
                }
                break;
            case "string": {
                const node = document.createTextNode(tokens[i].value);
                fragment.appendChild(node);
                break;
            }
            case "closetag": {
                break;
            }
            default: {
                fragment.appendChild(
                    document.createTextNode(tokens[i].value)
                );
            }
        }
    }
    return fragment;
}

const textareaEntrada = document.getElementById('historia_projeto');
const divSaida = document.getElementById('saida-historia-projeto');
if (!textareaEntrada) throw new Error("sem entrada de texto");
if (!divSaida) throw new Error("sem div de saída");

divSaida.replaceChildren(parser(lexer(textareaEntrada.value)));