(
    echo "From me  `LANG=C date`"
    echo "X-Command-Line-Arguments: $0 $*"
    cat
) >> `dirname $0`/mailbox
