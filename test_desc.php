<?php $c = new PDO("mysql:host=localhost;port=3307;dbname=botanero_ventas", "root", ""); $s = $c->query("DESCRIBE empresas"); foreach($s->fetchAll() as $r) { echo $r["Field"] . "\n"; }
