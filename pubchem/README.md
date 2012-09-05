#PubChem Parser


##Introduction

The pubchem parser is a script to download and convert pubchem xml into valid bio2rdf N-TRIPLES/N-QUADS. This script allows mirroring of the pubchem FTP site and will only download those files which have changed since last running of the script.

###Requirements

This script makes use of curlftpfs to mount and sync files between the pubchem
servers and local directory:

*curlftpfs
*fuse4x

###Installation

Ubuntu

	> sudo apt-get install curlftpfs

This command will install all the necessary packages to run the script

Mac 

The easiest way to install fuze4x on a mac (tested on 10.8) is to use the 
homebrew package manager (link). Once installed run:

	> brew install curlftpfs

In order for FUSE-based filesystems to work, the fuse4x kernel extension
must be installed by the root user:

 > sudo cp -rfX /usr/local/Cellar/fuse4x-kext/0.9.1/Library/Extensions/fuse4x.kext /Library/Extensions
 > sudo chmod +s /Library/Extensions/fuse4x.kext/Support/load_fuse4x

If upgrading from a previous version of Fuse4x, the old kernel extension
will need to be unloaded before performing the steps listed above. First,
check that no FUSE-based filesystems are running:

  > mount -t fuse4x

Unmount all FUSE filesystems and then unload the kernel extension:

  > sudo kextunload -b org.fuse4x.kext.fuse4x