#!/bin/bash
# Quick deploy for Costa Colbun prueba
cd /tmp && rm -rf costa-deploy
git clone --depth 1 --branch claude/redesign-website-lake-style-a1l3S https://github.com/jpchs1/muelleflotante-cl.git costa-deploy
cp costa-deploy/prueba/index.html ~/costacolbun.cl/prueba/index.html
cp costa-deploy/prueba/css/style.css ~/costacolbun.cl/prueba/css/style.css
cp costa-deploy/prueba/js/main.js ~/costacolbun.cl/prueba/js/main.js
rm -rf /tmp/costa-deploy
# Fix image names
cd ~/costacolbun.cl/prueba/img/
for f in *.jpg.png; do [ -f "$f" ] && mv "$f" "${f%.jpg.png}.jpg"; done
for f in *.jpg.jpg; do [ -f "$f" ] && mv "$f" "${f%.jpg.jpg}.jpg"; done
echo 'DEPLOY EXITOSO'
ls -la ~/costacolbun.cl/prueba/img/
