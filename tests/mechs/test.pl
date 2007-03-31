#!/usr/bin/perl
# Test a project creation

use strict;
use warnings;
use WWW::Mechanize;
use Test::More qw(no_plan);

use Digest::MD5 qw(md5_hex);

$ENV{SAVANE_CONF} = '/tmp/savane/savane';
use Savane;

my $homepage_url = 'http://localhost:50080';

my $agent = WWW::Mechanize->new();
my $admin_name = 'admin';
my $admin_pass = 'admin';

# New user
my $user1_number = 0;
my $user1_name = '';
my $user1_status = '';
do {
    $user1_number++;
    $user1_name = 'testuser' . $user1_number;
    $user1_status = GetUserSettings($user1_name, 'status');
} while (defined($user1_status));
my $user1_pass = $user1_name;
my $user1_realname = "Test User $user1_number";

$agent->get($homepage_url);
$agent->follow_link(text => 'New User', n => '1');
$agent->form_number(2);
$agent->field('form_loginname', $user1_name);
$agent->field('form_pw', $user1_pass);
$agent->field('form_pw2', $user1_pass);
$agent->field('form_realname', $user1_realname . "'");
$agent->field('form_email', $user1_name.'@localhost');
$agent->field('website', 'http://'); # anti-spam test or sthing
$agent->click('update');

ok(GetUserSettings($user1_name, 'status') eq 'P', 'Register user');

my $confirm_hash = GetUserSettings($user1_name, 'confirm_hash');
$agent->get("$homepage_url/account/verify.php?confirm_hash=$confirm_hash");
$agent->form_number(2);
$agent->field('form_loginname', $user1_name);
$agent->field('form_pw', $user1_pass);
$agent->field('website', 'http://');
$agent->click('update');

ok(GetUserSettings($user1_name, 'status') eq 'A', 'Confirm user');



# Create a new project
my $group1_number = 0;
my $group1_system_name = '';
my $group1_status = '';
do {
    $group1_number++;
    $group1_system_name = 'testgroup' . $group1_number;
    $group1_status = GetGroupSettings($group1_system_name, 'status');
} while (defined($group1_status));
my $group1_full_name = "Test Group $group1_number";
# Generate easily recognizeable name for Mechanize:
$group1_full_name .= ' [' . md5_hex($group1_system_name) . ']';

$agent->follow_link(text => 'Register New Project', n => '1');
$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_comments', '');
# Test string escaping / quoting
$agent->field('form_purpose', "'");
$agent->field('form_required_sw', '');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_unix_name', $group1_system_name);
$agent->field('form_full_name', $group1_full_name);
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_license', 'gpl');
$agent->field('form_license_other', '');
$agent->click('Submit');

$agent->form_number(2);
# Skip recap
#$agent->field('form_comments', '');
#$agent->field('form_purpose', "'");
#$agent->field('group_type', '1');
#$agent->field('form_required_sw', '');
#$agent->field('form_license', 'gpl');
#$agent->field('form_full_name', 'test');
#$agent->field('form_license_other', '');
$agent->click('i_agree');

# Check that the project exists
ok(GetGroupSettings($group1_system_name, 'status') eq 'P', 'Register project');

# Log out
$agent->follow_link(text => 'Logout', n => '1');


# Check that the associated task was created
# TODO


# Login in as admin
$agent->get('http://localhost:50080/');
$agent->follow_link(text => 'Login', n => '1');

$agent->form_number(2);
$agent->field('form_loginname', 'admin');
$agent->field('form_pw', 'admin');
$agent->click('login');

$agent->follow_link(text => 'Become Superuser', n => '1');
$agent->follow_link(text => 'Pending projects', n => '1');
$agent->follow_link(text => "Submission of $group1_full_name", n => '1');
$agent->follow_link(text => 'Group Administration', n => '2');
#$agent->follow_link(n => '43'); # activate
#$agent->follow_link(n => '45'); # configure
$agent->form_number(2);
#$agent->field('form_name', 'test');
$agent->field('form_status', 'A');
$agent->click('update');


ok(GetGroupSettings($group1_system_name, 'status') eq 'A', 'Approve project');
