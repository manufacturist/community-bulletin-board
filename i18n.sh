for po_file in ./i18n/*.po;
    do msgfmt "$po_file" -o "${po_file%.po}/LC_MESSAGES/i18n.mo"
done