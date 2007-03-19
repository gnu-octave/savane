#!/usr/bin/perl
use Data::Dumper;
use strict;

sub md5sum {
    my $result = `md5sum $_[0] | awk '{ print \$1 }'`;
    chomp($result);
    return $result;
}

my @images = split(' ', `find *.theme -type f -name "*.orig.png" | xargs -n1 basename | sort | uniq | tr '\n' ' '`);

#print join(',', @images);
#print "\n";


#print md5sum('Cafe.theme/icon.png');
#print "\n";

# Making the assumtion that files named differently have different content

my %sums = ();
foreach my $theme (<*.theme>) {
    foreach my $image (@images) {
	my $sum;
	if (-e "$theme/$image") {
	    $sum = md5sum("$theme/$image");
	    push(@{$sums{$image}{$sum}}, $theme);
	}
    }
}
print Dumper(%sums);

# Display files that the same in all themes:
foreach my $image (keys %sums) {
    my $max;
    my $max_count = -1;
    foreach my $checksum (keys %{$sums{$image}}) {
	my $count = scalar(@{$sums{$image}{$checksum}});
	if ($count > $max_count) {
	    $max_count = $count;
	    $max = $checksum;
	}
    }
    #print "max[$image] = $max ($max_count)\n";
    #foreach (@{$sums{$image}{$max}}) {
    #    print "  ";
    #   print;
    #   print "\n";
    #}
    print "cp $sums{$image}{$max}[0]/$image common\n";
    foreach my $theme (@{$sums{$image}{$max}}) {
	my $file = "$theme/$image";
	print "svn del $file\n";
	print "ln -s ../common/$image $theme/\n";
        print "svn add $file\n";
    }
    print "\n";
}

# TODO: trier par nombre d'élément pour un checksum, prendre le
# checksum le plus haut, s'il est plus grand que 2, alors organiser
# une migration vers common/ + symlinks
