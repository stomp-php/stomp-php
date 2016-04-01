Changelog stomp-php
-------------------

2.1.0
-----

2.1.0 based fork
----------------

- fixed travis ci setup
- added more unit tests (removed ssl unit tests, they don't depend on stomp client)
- refactoring extracted Connection, Parser, Protocol, ActiveMq, RabbitMq, ConnectionException, ErrorFrameException, UnexpectedResponseException
- fixed dead loops caused by connection exceptions
- removed the possibility to auto-reconnect outside the connect process (it's quite intransparent and can lead to much more problems than expected)
- added a read buffer seen at https://github.com/camronlevanger/stomp-php/commit/8bca4a55b5db8493f543c7f2d1db13d42455e19d

2.2.1
-----

- fixed deadloop on connection exception (https://github.com/fin-sn-de/stomp-php/issues/1)

2.2.2
-----
- This is the last version which is compatible to original fork!
- all sync operations will throw an exception if they are left unconfirmed (https://github.com/fin-sn-de/stomp-php/issues/2)

2.2.3
-----
- setting a client id will not longer lead to an durable subscription (https://github.com/fin-sn-de/stomp-php/issues/3)
- `subscribe` and `subscribe` now have a new parameter `durable` which is `false` as default

2.2.4
-----
- fixed possible nullpointer on broken connections

3.0.0
-----
- This version aims to cover all current forks from https://github.com/dejanb/stomp-php.
- moved to namespace `Stomp`
- changed back to `fgets` (https://github.com/dejanb/stomp-php/pull/22)
- updated travis-ci config
- merged unit tests for `Stomp\Frame` (https://github.com/chuhlomin/stomp-php/)
- use stream_socket_client (https://github.com/dejanb/stomp-php/pull/25)
- enable sync mode by default (https://github.com/stomp-php/stomp-php/pull/19)
- updated documentation (https://github.com/stomp-php/stomp-php/wiki)

3.0.1
-----
- changed back to `fread`, FrameParser should handle more than one Frame (https://github.com/stomp-php/stomp-php/issues/21)
 
3.0.2
-----
- changed to `stream_read_line` (https://github.com/stomp-php/stomp-php/issues/24, https://gist.github.com/arjenm/4ae508767b1af73c63f5)
- add support for `content-length` header (https://github.com/stomp-php/stomp-php/issues/23, https://gist.github.com/arjenm/7bab3a10f6d2460c7891)
- Updated function testsuite for different brokers (amq,aplo,rabbit), update travis-ci.

3.0.3
-----
 - allow to pass headers on `connect` (https://github.com/stomp-php/stomp-php/pull/27)

3.0.4
-----
 - stomp won't call `disconnect` on `connection` when host fails (https://github.com/stomp-php/stomp-php/issues/28, https://gist.github.com/arjenm/f603b982f3ff701f3462)
 
3.0.5
-----
 - do not error when received header value is null (https://github.com/stomp-php/stomp-php/issues/30)

4.0.0 (Full Stomp 1.1 / 1.2 Support)
------------------------------------

- Updated functional testsuite for different brokers (amq,aplo,rabbit), update travis-ci.
- Update Parser in order to be compliant with stomp-1.2 (https://stomp.github.io/stomp-specification-1.2.html)
- ACK Mode is now `auto` by default. 
- Implement StateMachine Pattern `StatefulStomp`.
- Restructure project, new Namespaces `Protocol`, `Transport`, `Network`.
- Make Client much smaller, remove all non low level methods to `SimpleStomp`.
- Testsuite rework...
- Move examples to https://github.com/stomp-php/stomp-php-examples.
- add utils for `ActiveMq` durable subscription and `Apollo` queue browser.

4.0.1
-----
- do not throw read exception if the next byte after a complete read is a zero byte. (https://github.com/stomp-php/stomp-php/issues/39)
- allow to reset the parser internal state and buffer. (https://github.com/stomp-php/stomp-php/issues/40)