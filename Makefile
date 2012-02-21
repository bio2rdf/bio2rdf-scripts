include Makefile.conf

TARGETS= \
        chebi \
        omim

N3_FILES=$(foreach tgt, $(TARGETS), $(N3_DIR)/$(tgt).n3)

%.n3:
	cd $(notdir $*); make n3

%.n3.gz:
	cd $(notdir $*); make n3.gz

n3: $(N3_FILES)
n3.gz: $(foreach n3, $(N3_FILES), $(n3).gz)

all: n3

%_clean_n3:
	cd $(notdir $*); make clean_n3

%_clean_downloads:
	cd $(notdir $*); make clean_downloads

clean_n3: $(foreach tgt, $(TARGETS), $(tgt)_clean_n3)
clean_downloads: $(foreach tgt, $(TARGETS), $(tgt)_clean_downloads)

clean: clean_n3 clean_downloads

.PHONY: clean

