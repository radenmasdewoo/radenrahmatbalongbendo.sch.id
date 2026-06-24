#!/bin/bash
# Restore izin website: kepemilikan www-data (nginx) agar server baca file dengan aman.
# Jalankan dengan: sudo ./restore-permissions.sh
# Setelah ini, untuk mengedit lagi perlu: sudo chown -R adminrr:adminrr /mnt/webdata/htdocs/mi

set -e
WEBROOT="/mnt/webdata/htdocs/mi"
OWNER="${1:-www-data}"

echo "Mengembalikan kepemilikan ke $OWNER..."
chown -R "$OWNER:$OWNER" "$WEBROOT"

echo "Mengatur permission (755 folder, 644 file)..."
find "$WEBROOT" -type d -exec chmod 755 {} \;
find "$WEBROOT" -type f -exec chmod 644 {} \;

# .htaccess dan file sensitif tetap 644
echo "Selesai. Website dapat dibaca oleh nginx, hanya $OWNER/root yang dapat menulis."
echo "Untuk mengedit lagi dari Cursor: sudo chown -R adminrr:adminrr $WEBROOT"
