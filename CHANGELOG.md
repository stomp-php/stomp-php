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

WIP
---
- Updated function testsuite for different brokers (amq,aplo,rabbit), update travis-ci.
