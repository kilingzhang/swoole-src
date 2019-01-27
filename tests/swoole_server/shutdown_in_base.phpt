--TEST--
swoole_server: shutdown in base mode
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';
$pm = new ProcessManager;
$pm->initRandomData(1);
$pm->parentFunc = function () use ($pm) {
    go(function () use ($pm) {
        $client = new Co\Client(SWOOLE_SOCK_TCP);
        assert($client->connect('127.0.0.1', $pm->getFreePort()));
        assert($client->send($pm->getRandomData()) > 0);
    });
};
$pm->childFunc = function () use ($pm) {
    $server = new Swoole\Server('127.0.0.1', $pm->getFreePort(), SWOOLE_BASE);
    $server->set(['worker_num' => mt_rand(2, 4), 'log_file' => '/dev/null']);
    $server->on('start', function () use ($pm) {
        echo "START\n";
        $pm->wakeup();
    });
    $server->on('receive', function (Swoole\Server $server, int $fd, int $rid, string $data) use ($pm) {
        assert($data === $pm->getRandomData());
        $server->shutdown();
    });
    $server->on('shutdown', function () {
        echo "SHUTDOWN\n";
    });
    $server->start();
};
$pm->childFirst();
$pm->run();
$pm->expectExitCode(0);
?>
--EXPECT--
START
SHUTDOWN