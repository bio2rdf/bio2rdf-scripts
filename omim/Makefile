include ../Makefile.conf

DOWNLOAD_DIR=$(DOWNLOAD_PATH)/omim
N3_FILE=$(N3_PATH)/omim.n3

n3: $(N3_FILE)
n3.gz: $(N3_FILE).gz
all: n3

$(DOWNLOAD_DIR):
	mkdir -p $(DOWNLOAD_DIR)
	./omim-wget.sh $(DOWNLOAD_DIR)

$(N3_FILE): $(DOWNLOAD_DIR)
	perl omim-genemap2n3.pl $(DOWNLOAD_DIR)/ftp.ncbi.nih.gov/repository/OMIM/ARCHIVE/genemap 1>$(N3_FILE) 2>$(N3_FILE).err

$(N3_FILE).gz: $(DOWNLOAD_DIR)
	perl omim-genemap2n3.pl $(DOWNLOAD_DIR)/ftp.ncbi.nih.gov/repository/OMIM/ARCHIVE/genemap | gzip 1>$(N3_FILE).gz 2>$(N3_FILE).err

clean_downloads:
	rm -Rf $(DOWNLOAD_DIR)

clean_n3:
	rm -f $(N3_FILE) $(N3_FILE).gz $(N3_FILE).err

clean: clean_n3 clean_downloads
