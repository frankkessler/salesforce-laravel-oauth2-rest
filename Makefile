help:
	@echo "Please use \`make <target>' where <target> is one of"
	@echo "  start-server   to start the test server"
	@echo "  stop-server    to stop the test server"
	@echo "  test           to perform unit tests.  Provide TEST to perform a specific test."

start-server: stop-server
	node --version && npm --version && node tests/server.js &> /dev/null &

stop-server:
	@PID=$(shell ps axo pid,command \
	  | grep 'tests/server.js' \
	  | grep -v grep \
	  | cut -f 1 -d " "\
	) && [ -n "$$PID" ] && kill $$PID || true

test: start-server
	vendor/bin/phpunit
	$(MAKE) stop-server