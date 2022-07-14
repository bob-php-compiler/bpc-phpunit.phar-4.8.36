PHPUNIT_FILES = $(shell grep -o ".*\.php" loader.php | sed "s/include __DIR__ . '\///")

libphpunit_u-4.4a.so: loader.php $(PHPUNIT_FILES)
	bpc -v -l phpunit loader.php $(PHPUNIT_FILES)

install: libphpunit_u-4.4a.so
	bpc -l phpunit --install

clean:
	@rm -v libphpunit*
	@rm -v phpunit.heap
	@rm -v phpunit.sch
	@rm -rf .bpc-build-* md5.map
