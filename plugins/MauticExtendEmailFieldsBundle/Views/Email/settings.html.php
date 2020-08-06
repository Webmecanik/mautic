<?php

$alias = $form->vars['name'];
?>


<div class="row">
    <div class="form-group col-xs-12 ">
        <label class="control-label" for="<?php echo $alias; ?>_extra1" data-toggle="tooltip" data-container="body"
               data-placement="top" title="<?php echo $labelExtra1; ?>"><?php echo $labelExtra1; ?> <i
                class="fa fa-question-circle"></i></label>
        <div class="input-group">
                    <span class="input-group-addon preaddon">
        <i class="fa fa-envelope"></i>
    </span>
            <input autocomplete="false" type="text"
                   id="<?php echo $alias; ?>_extra1" name="extra1" value="<?php echo $extra1; ?>" class="form-control"
                   autocomplete="false"/>

        </div>
    </div>
</div>
<div class="row">
    <div class="form-group col-xs-12 ">
        <label class="control-label" for="<?php echo $alias; ?>_extra2" data-toggle="tooltip" data-container="body"
               data-placement="top" title="<?php echo $labelExtra2; ?>"><?php echo $labelExtra2; ?> <i
                class="fa fa-question-circle"></i></label>
        <div class="input-group">
                    <span class="input-group-addon preaddon">
        <i class="fa fa-envelope"></i>
    </span>
            <input autocomplete="false" type="text"
                   id="<?php echo $alias; ?>_extra2" name="extra2" value="<?php echo $extra2; ?>" class="form-control"
                   autocomplete="false"/>

        </div>
    </div>
</div>


