function isLetter(char) {
    return char = char.charCodeAt(0), (char >= 97 && char <= 122) || (char >= 65 && char <= 90);
}

function lexer(str) {

    const tokens = [];
    if (!str) return tokens;

    for (let i = 0, len = str.length; i < len; ++i) {

        if (str[i] === '<' && str[i+1] === '#') {
            ++i;
            const tagTk = { type: 'tag', value: '' };
            while (str[i+1] === ' ') ++i;
            while (str[++i] && str[i] !== ' ' && !(str[i] === '#' && str[i+1] === '>')) {
                tagTk.value += str[i];
            }
            if (str[i] !== ' ') --i;
            tokens.push(tagTk);
            continue;
        }

        if (str[i] === '#' && str[i+1] === '>') {
            ++i;
            tokens.push({ type: 'closetag', value: '' });
            if (str[i+1] === ' ') ++i;
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
        while (str[i] && !(str[i] === '<' && str[i+1] === '#') && !(str[i] === '#' && str[i+1] === '>')) {
            if (str[i] === '\\') {
                ++i;
            }
            tk.value += str[i];
            ++i;
        }
        if (str[i] !== ' ') --i;
        tokens.push(tk);

    }
    return tokens;
}

function parser(tokens) {

    if (!tokens) return document.createTextNode("Conteúdo da história não informado.");

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
                        fragment.appendChild(title);
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
                    case "bloco": {
                        const bloco = document.createElement("div");
                        bloco.className = "historia-bloco";
                        while (tokens[++i].type !== "closetag") {
                            bloco.innerText += tokens[i].value;
                        }
                        --i;
                        fragment.appendChild(bloco);
                    }
                    case "br": {
                        fragment.appendChild(document.createElement("br"));
                        break;
                    }
                    default: {
                        fragment.appendChild(
                            document.createTextNode('<' + tokens[i].value + '>')
                        );
                        break;
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
                break;
            }
        }
    }
    return fragment;
}