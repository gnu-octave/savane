# Default spamassassin conffile for Savane spamcheck.
# As filtering post on a www differs a bit from mails, some tuning is 
# welcome.


## BAYESIAN FILTER
## Spamassassin to be effective has to learn from found spams. 
## (the bayes_path directory must exists, and be writable to the
## spamassassin user)
use_bayes 1
bayes_path /var/spool/spamassassin/bayes
bayes_file_mode 0666
auto_learn 1
score BAYES_20 2
score BAYES_40 3
score BAYES_60 4
score BAYES_80 7
score BAYES_95 8
score BAYES_99 10


## INTERNAL NETWORKS
## You should add here internal network (company or institute network that 
## you trust that wont send spams)
#internal_networks ?.?.?.?
#internal_networks ?.?.?.?


## RAZOR
## if razor is set up on your system, you can uncomment this (check paths)
#use_razor2 1
#razor_config /etc/mail/razor/razor-agent.conf
#score RAZOR2_CF_RANGE_51_100 3.2
