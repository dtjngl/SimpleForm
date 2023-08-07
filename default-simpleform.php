<?php namespace ProcessWire; 

$simpleForm = wire('modules')->get('SimpleForm');

if ($config->ajax) :

    $simpleForm->handleAJAX($input);
    return $this->halt();

else : ?>

    <div id="content">

		<?php

			// $simpleForm->handleStaticContent($input);
			echo $simpleForm->renderSimpleForm();
			echo $simpleForm->addScripts();

		?>

	</div>

<?php 

endif; 


?>
