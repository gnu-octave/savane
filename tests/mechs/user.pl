use strict;
our ($homepage_url, $agent);

# New pending user
sub register_user {
    my ($agent, $user_name, $user_pass, $user_realname) = @_;
	

    my $user_number = 0;
    if (!defined($user_name)) {
	my $user_status;
	do {
	    $user_number++;
	    $user_name = 'testuser' . $user_number;
	    $user_status = GetUserSettings($user_name, 'status');
	} while (defined($user_status));
	my $user_realname = "Test User $user_number";
    }
    if (!defined($user_pass)) {
	$user_pass = $user_name;
    }
    if (!defined($user_realname)) {
	$user_realname = "Test User $user_name";
    }

    $agent->get($homepage_url);
    $agent->follow_link(text => 'New User', n => '1');
    $agent->form_number(2);
    $agent->field('form_loginname', $user_name);
    $agent->field('form_pw', $user_pass);
    $agent->field('form_pw2', $user_pass);
    $agent->field('form_realname', $user_realname);
    $agent->field('form_email', $user_name.'@localhost');
    $agent->field('website', 'http://'); # anti-spam test or sthing
    $agent->click('update');

    return ($user_name, $user_pass);
}

sub confirm_user {
    my ($user_name, $user_pass) = @_;
    my $confirm_hash = GetUserSettings($user_name, 'confirm_hash');
    $agent->get("$homepage_url/account/verify.php?confirm_hash=$confirm_hash");
    $agent->form_number(2);
    $agent->field('form_loginname', $user_name);
    $agent->field('form_pw', $user_pass);
    $agent->field('website', 'http://');
    $agent->click('update');
}

1;
