<?php
	include '../include/functions/general.php';
	include '../include/header.php';
?>


<br/>
<br/>
<br/>

<div id="test">
	<div class="testGroup">
		<div class="testArea">
			<div class="testName">Stahování</div>
			<canvas id="dlMeter" class="meter"></canvas>
			<div id="dlText" class="meterText"></div>
			<div class="unit">Mbps</div>
		</div>
		<div class="testArea">
			<div class="testName">Odesílání</div>
			<canvas id="ulMeter" class="meter"></canvas>
			<div id="ulText" class="meterText"></div>
			<div class="unit">Mbps</div>
		</div>
	</div>
	<div class="testGroup">
		<div class="testArea">
			<div class="testName">Odezva</div>
			<canvas id="pingMeter" class="meter"></canvas>
			<div id="pingText" class="meterText"></div>
			<div class="unit">ms</div>
		</div>
		<div class="testArea">
			<div class="testName">Zpoždění</div>
			<canvas id="jitMeter" class="meter"></canvas>
			<div id="jitText" class="meterText"></div>
			<div class="unit">ms</div>
		</div>
	</div>
	<div id="ipArea">
		Vaše IP adresa: <span id="ip"></span>
	</div>

    <br/>
    <br/>
    <br/>

    <div id="startStopBtn" onclick="startStop()"></div>
</div>

<script type="text/javascript">
    setTimeout(initUI,100);
</script>

<?php
    include '../include/footer.php';
?>