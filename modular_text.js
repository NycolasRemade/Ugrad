const blocoInicial = document.getElementById('historia_projeto');

function lexer(str) {

    const tokens = [];
    for (let i = 0, len = str.length; i < len; ++i) {

        if (str[i] === '<') {
            const tagTk = { type: 'tag', value: '' };
            while (str[++i] && str[i] !== ' ' && str[i] !== '>') {
                tagTk.value += str[i];
            }
            tokens.push(tagTk);
            continue;
        }

        if (str[i] === '>') {
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

        const tk = { type: 'text', value: '' };
        while (str[i] && str[i] !== '<' && str[i] !== '>') {
            if (str[i] === '\\') {
                ++i;
            }
            tk.value += str[i];
            ++i;
        }
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
                    case "img": {
                        ++i;
                        if (tokens[i].type === "string") {
                            const img = document.createElement("div");
                            img.className = ""; // COLOCAR CLASSE DE IMAGEM AQUI
                            img.style.backgroundImage = "url('" + tokens[i].value + "')";
                            fragment.appendChild(img);
                        }
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
            default: {
                fragment.appendChild(
                    document.createTextNode(tokens[i].value)
                );
            }
        }
    }
    return fragment;
}