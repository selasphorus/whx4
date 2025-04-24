<?php
// Assume these are passed into scope before including this file:
$availableModules   = $availableModules ?? [];
$activeModules      = $activeModules ?? [];
$enabledPostTypes   = $enabledPostTypes ?? [];
?>

<div class="wrap">
    <h1>Plugin Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'whx4_plugin_settings_group' ); ?>
        <?php do_settings_sections( 'whx4_plugin_settings' ); ?>

        <table class="form-table" id="whx4-settings-table">
            <tbody>
                <?php foreach ( $availableModules as $key => $moduleClass ) :
                    $isActive  = in_array( $key, $activeModules, true );
                    $module    = class_exists( $moduleClass ) ? new $moduleClass() : null;
                    $postTypes = $module ? $module->getPostTypes() : [];
                ?>
                    <tr>
                        <th scope="row">
                            <label>
                                <input
                                    type="checkbox"
                                    class="module-toggle"
                                    name="whx4_plugin_settings[active_modules][]"
                                    value="<?php echo esc_attr( $key ); ?>"
                                    <?php checked( $isActive ); ?>
                                />
                                <?php echo esc_html( $key ); ?>
                            </label>
                        </th>
                        <td></td>
                    </tr>

                    <?php if ( ! $module ) : ?>
                        <tr>
                            <td colspan="2">Missing class: <?php echo esc_html( $moduleClass ); ?></td>
                        </tr>
                    <?php else : ?>
                        <tr id="post-types-<?php echo esc_attr( $key ); ?>" class="post-type-row" <?php if ( ! $isActive ) echo 'style="display:none;"'; ?>>
                            <td colspan="2" style="padding-left: 30px;">
                                <?php foreach ( $postTypes as $slug => $label ) :
                                    $isEnabled = isset( $enabledPostTypes[ $key ] ) && in_array( $slug, $enabledPostTypes[ $key ], true );
                                ?>
                                    <label style="display:block;">
                                        <input
                                            type="checkbox"
                                            name="whx4_plugin_settings[enabled_post_types][<?php echo esc_attr( $key ); ?>][]"
                                            value="<?php echo esc_attr( $slug ); ?>"
                                            <?php checked( $isEnabled ); ?>
                                        />
                                        Enable <code><?php echo esc_html( $slug ); ?></code>: <?php echo esc_html( $label ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
