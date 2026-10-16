#!/bin/sh
# Baixa GrapesJS Community (versão fixa) para vendor/grapesjs/.
# Rode uma vez por deploy ou manualmente. Idempotente: pula se os arquivos existem.
# Mantém a licença MIT original (embutida no arquivo minificado).

set -e

VERSION="0.21.9"
BASE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
TARGET_DIR="$BASE_DIR/vendor/grapesjs"
mkdir -p "$TARGET_DIR"

download() {
    url="$1"
    dest="$2"
    if [ -s "$dest" ]; then
        echo "  já existe: $(basename "$dest")"
        return 0
    fi
    echo "  baixando $(basename "$dest")..."
    # Tenta unpkg primeiro, cai no jsdelivr se falhar
    if ! curl -sS -L --fail --connect-timeout 15 --max-time 90 -o "$dest" "$url"; then
        alt=$(echo "$url" | sed 's#unpkg.com#cdn.jsdelivr.net/npm#')
        echo "  tentando mirror: $alt"
        if ! curl -sS -L --fail --connect-timeout 15 --max-time 90 -o "$dest" "$alt"; then
            echo "  ERRO: falha ao baixar $(basename "$dest")" >&2
            return 1
        fi
    fi
    # Sanity check: arquivo não pode ter < 10KB (abaixo disso é erro HTML)
    size=$(wc -c < "$dest")
    if [ "$size" -lt 10240 ]; then
        echo "  ERRO: $(basename "$dest") veio muito pequeno ($size bytes)." >&2
        rm -f "$dest"
        return 1
    fi
}

echo "Vendorizando GrapesJS v$VERSION em $TARGET_DIR..."
download "https://unpkg.com/grapesjs@$VERSION/dist/grapes.min.js"  "$TARGET_DIR/grapes.min.js"
download "https://unpkg.com/grapesjs@$VERSION/dist/css/grapes.min.css" "$TARGET_DIR/grapes.min.css"

cat > "$TARGET_DIR/VERSION" <<EOF
grapesjs $VERSION
Fonte: https://unpkg.com/grapesjs@$VERSION/
Licença: BSD-3-Clause (preservada no cabeçalho dos arquivos .min.js e .min.css)
EOF

echo "GrapesJS vendorizado com sucesso."
