<div id="err_message">

<h1>CHYBA: Nenalezena metoda</h1>
<p>Zkontrolujte, zda-li jste zadali správnou URL adresu.</p>
<p>Pokud ano, ale zatím jste nevytvořili příslušnou metodu, využijte následující kostru:</p>

<div>soubor: <em>/controllers/<?= HBasics::underscore(HRouter::$controller) ?>_controller.php</em></div>
<pre>
public function <?=HRouter::$action?>() {
	// váš kód
}
</pre>
<p>Po vypnutí ladícího režimu se při chybě zobrazí klasická E404.</p>

</div>