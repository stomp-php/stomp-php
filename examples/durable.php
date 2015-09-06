<?php
require __DIR__ . '/../vendor/autoload.php';
/**
 *
 * Copyright (C) 2009 Progress Software, Inc. All rights reserved.
 * http://fusesource.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// include a library

use Stomp\Stomp;

// create a producer
$producer = new Stomp('tcp://localhost:61613');
// create a consumer
$consumer = new Stomp('tcp://localhost:61613');
$consumer->setReadTimeout(1);
// set clientId on a consumer to make it durable
$consumer->clientId = 'test';
// connect
$producer->connect();
$consumer->connect();

// subscribe to the topic
$consumer->subscribe('/topic/test', null, true, true);


// send a message to the topic
$producer->send('/topic/test', 'test-1');
echo "Message 'test-1' sent to topic\n";

// receive a message from the topic
$msg = $consumer->readFrame();

// do what you want with the message
if ($msg != null) {
    echo "Message '$msg->body' received from topic\n";
    $consumer->ack($msg);
} else {
    echo "Failed to receive a message\n";
}



// disconnect durable consumer
$consumer->unsubscribe('/topic/test');
$consumer->disconnect();
echo "Disconnecting consumer\n";

// send a message while consumer is disconnected
$producer->send('/topic/test', 'test-2');
echo "Message 'test-2' sent to topic\n";


// reconnect the durable consumer
$consumer = new Stomp('tcp://localhost:61613');
$consumer->clientId = 'test';
//$consumer->connect('guest','guest');
$consumer->connect('admin', 'password');
$consumer->subscribe('/topic/test', null, true, true);
echo "Reconnecting consumer\n";

// receive a message from the topic
$msg = $consumer->readFrame();

// do what you want with the message
if ($msg != null) {
    echo "Message '$msg->body' received from topic\n";
    $consumer->ack($msg);
} else {
    echo "Failed to receive a message\n";
}

// disconnect
if ($consumer->getProtocol() instanceof \Stomp\Protocol\ActiveMq) {
    // activeMq Way
    $consumer->unsubscribe('/topic/test');
    $consumer->unsubscribe('/topic/test', null, true, true);
} else {
    // default (apollo way)
    $consumer->unsubscribe('/topic/test', null, true, true);
}

// this message will never be seen, since no durable subscriber is present
$producer->send('/topic/test', 'test-3');

$consumer->disconnect();
$producer->disconnect();
