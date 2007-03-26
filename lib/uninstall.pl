#!/usr/bin/perl
# Uninstall a Perl module, which MakeMaker can't do anymore

# Copied from http://www.perlcircus.org/moremod.shtml and slightly modified.
# "Unless otherwise noted, all code snippets on this site should be considered public domain."
# -Michael Mathews

use IO::Dir;
use ExtUtils::Packlist;
use ExtUtils::Installed;

my $install = ExtUtils::Installed->new();
my $module = $ARGV[0] or die "Usage: $0 Module::Name\n"; 

foreach my $file ($install->files($module)) {
    ask_delete($file);
}
ask_delete($install->packlist($module)->packlist_file());

sub ask_delete {
    my $file = shift;
    
    (unlink $file)? print qq(Deleted module file "$file".\n)
        : warn qq(Couldn't delete "$file". $!\n); #'

    foreach my $dir ($install->directory_tree($module)) {
        next unless(is_empty($dir));
        
        (rmdir($dir))? print qq(Deleted empty dir "$dir"\n)
            : warn qq(Couldn't delete "$dir" $!\n); #'
    }
}

sub is_empty {
    my $dh = IO::Dir->new(+shift) or return;
    my $count = scalar(grep{!/^\.\.?$/} $dh->read());
    $dh->close();
    return($count==0);
}
