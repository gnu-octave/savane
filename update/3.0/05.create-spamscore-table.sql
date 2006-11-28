CREATE TABLE `trackers_spamscore` (
  `id` int(11) NOT NULL auto_increment,
  `score` int(2) NOT NULL default '1',
  `affected_user_id` int(11) NOT NULL default '0',
  `reporter_user_id` int(11) NOT NULL default '0',
  `artifact` varchar(16) NOT NULL,
  `item_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
);

# Explanation of the table
# 
# score will usually be equal to the weight of the mark:
#   it will be used to compute score saved in the spamscore field of items
#   and comments. We need the spamscore field in others table to go light
#   on the server
#   
# 
#
# affected_user_id will be set when the a spamscore mark as been added by
# a project member to a comment item. this will be used to determine default
# user spamscore. 
# If user spamscore is equal or higher to 4, new items posted by the user will
# have a default spamscore of 4, not 5, to forbid censorship by users.
# However, the user will be listed and the site admin encouraged to take
# action.
# Indeed, affected_user_id wont be set to mark a new item of a user that have
# a spamscore.
# It is also important to note that the user spamscore wont be the sum of the
# score of his items but by the sums of the items marked as spam. Which means
# that if a user posted 2 items that got each a score of 3, his own score
# is 2, not 6.
# 
# reported_user_id will be set when the spamscore mark as been set by 
# a valid user. the purpose is to make sure an account mark as spam an
# object only once
#
# comment_id will be used to determine which comment we talk about. 0 
# will refer to the item original submission - the item itself.
#
