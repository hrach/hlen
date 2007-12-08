<div id="err_message">

<h1>SQL chyba</h1>
<p>Hlášení serveru:</p>
<ul>
	<li><?= $arg[0]->getMessage() ?></li>
</ul>

<p>Trasování chyby:</p>
<pre>
<?= $arg[0]->getTraceAsString() ?>
</pre>
</div>