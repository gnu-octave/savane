require 'user.pl';

my ($user1_name, $user1_pass) = register_user($agent);
ok(GetUserSettings($user1_name, 'status') eq 'P', 'Register user');

confirm_user($user1_name, $user1_pass);
ok(GetUserSettings($user1_name, 'status') eq 'A', 'Confirm user');

1;
