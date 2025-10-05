<div class="whx4-supernatural">
	<p><strong>How many Monsters?:</strong> <?php echo (int)$stats['monsters']; ?></p>
	<p><strong>Blue Monsters:</strong> <pre><?php //echo print_r($posts, true); ?></pre></p>
	<?php 
	foreach ($posts as $post) {
	    echo $post->post_title."<br />";
	}
	?>
	<p><strong>Debug:</strong> <pre><?php //echo print_r($debug, true); ?></pre></p>
</div>