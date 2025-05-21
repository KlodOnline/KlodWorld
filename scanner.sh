#!/bin/bash

# Vérifier si un argument (répertoire) est passé
if [ -z "$1" ]; then
    echo "Usage: $0 <répertoire>"
    exit 1
fi

DIRECTORY="$1"

# Vérifier si le répertoire existe
if [ ! -d "$DIRECTORY" ]; then
    echo "Erreur : Le répertoire '$DIRECTORY' n'existe pas."
    exit 1
fi

# Vérifier si la commande tree est disponible
if command -v tree > /dev/null 2>&1; then
    # Générer l'arbre avec tree
    tree -L 3 "$DIRECTORY"
else
    # Alternative avec find si tree n'est pas disponible
    echo "La commande 'tree' n'est pas installée. Utilisation de 'find' à la place."
    find "$DIRECTORY" -maxdepth 3 | sed "s|$DIRECTORY|.|"
fi
