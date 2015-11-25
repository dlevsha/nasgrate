<?php
require_once 'config.php';
$migrations = Process\Server::getInstance()->getSql();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Nasgrate - show SQL</title>
    <link href="/public/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/default.min.css">
    <script src="/public/highlight.min.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
</head>

<body>

<!-- Begin page content -->
<div class="container">
    <div class="page-header">
        <h1>Nasgrate</h1>
        <ul>
            <?php foreach ($migrations as $m) { ?>
                <li>
                    <a href="#<?php echo $m->getMigrationId(); ?>"><?php echo $m->getMigrationId(); ?></a> <?php echo $m->isExecuted() ? ' - executed' : ''; ?>
                </li>
            <?php } ?>
        </ul>
    </div>

    <?php foreach ($migrations as $m) { ?>
        <a name="<?php echo $m->getMigrationId(); ?>"></a>
        <div class="row alert bg-<?php echo $m->getMigrationId() ? 'success' : 'warning'; ?>">
            <p class="lead"><b>Name:</b> <?php echo $m->getMigrationId(); ?></p>
            <dl class="dl-horizontal">
                <dt>Create date</dt>
                <dd><?php echo $m->getDate(); ?></dd>
                <dt>Is skip</dt>
                <dd><?php echo $m->isSkip() ? 'yes' : 'no'; ?></dd>
                <dt>Is executed</dt>
                <dd><?php echo $m->isExecuted() ? 'yes' : 'no'; ?></dd>
                <dt>Description</dt>
                <dd><?php echo $m->getDescription(); ?></dd>
            </dl>
            <?php foreach ($m->getUpSql() as $k => $sql) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <pre><code class="sql"><?php echo $sql; ?></code></pre>
                    </div>
                    <div class="col-md-6">
                        <pre><code class="sql"><?php echo $m->getDownSqlItem($k); ?></code></pre>
                    </div>
                </div>
            <?php } ?>
        </div>
        <p>&nbsp;</p>
    <?php } ?>

</div>

</body>