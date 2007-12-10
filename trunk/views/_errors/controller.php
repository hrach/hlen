<div id="err_message">

<h1>CHYBA: Nenalezena třída controlleru</h1>
<p>Zkontrolujte, zda-li jste napsali správně název třídy controlleru nebo její soubor.</p>
<p>Využijte následující kostru:</p>

<div>soubor: <em>/controllers/<?= HBasics::underscore(HRouter::$controller) ?>_controller.php</em></div>
<pre>
class <?= HBasics::camelize(HRouter::$controller) ?>Controller extends Controller {
	public function index() {
		// váš kód
	}
}
</pre>
<p>Po vypnutí ladícího režimu se při chybě zobrazí klasická E404.</p>

</div>