# Fake sendmail to give to PHP, so that all mails are stored in a
# mailbox instead of actually being sent.
# Configure: php_admin_value sendmail_path /tmp/savane/fakesendmail.sh
# Read the mailbox: mutt -f /tmp/savane/mailbox
(
    echo "From me  `LANG=C date`"
    echo "X-Command-Line-Arguments: $0 $*"
    cat
) >> `dirname $0`/mailbox
