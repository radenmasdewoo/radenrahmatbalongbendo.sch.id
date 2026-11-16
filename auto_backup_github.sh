#!/bin/bash
# Auto backup website to GitHub - runs daily at 02:00 WIB

cd /mnt/webdata/htdocs

# Add all changes (respects .gitignore)
git add -A

# Check if there are changes to commit
if git diff --cached --quiet; then
    echo "$(date): No changes to commit"
    exit 0
fi

# Commit with timestamp
TS=$(date +%F_%H-%M-%S)
git commit -m "Auto backup ${TS}"

# Push to GitHub
git push origin main

echo "$(date): Backup pushed to GitHub"
