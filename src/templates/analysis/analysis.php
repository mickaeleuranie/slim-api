<?php
use api\enums\NutrientEnum;

if ($addCssJs) :
    $layoutData = [
        // add css
        'css' => ['analysis.css'],

        // add js
        'js' => [
            '/lib/jquery-3.2.1.min.js',
            'd3.min.js',
            'pie.js',
        ]
    ];
else :
    $layoutData = [];
endif;

$this->layout('layouts/empty', $layoutData);
?>

<div class="chart-container <?= $size; ?>">
    <div class="avcd-modal-content-container">
        <div class="avcd-cart-macronutrient-mini avcd-modal-content-third">
            <div class="avcd-cart-macronutrient-content">
                <div class="avcd-box-pie">
                    <div id="avcd-box-pie-carbs-wrapper"
                        class="avcd-box-pie-wrapper avcd-box-pie-carbs-wrapper <?= $size; ?>"
                        data-nutrient="carbs"
                        data-percent="[<?= $analysis['carbs']['percentage']; ?>]"
                        data-min="<?= $analysis['carbs']['range']['min']; ?>"
                        data-max="<?= $analysis['carbs']['range']['max']; ?>"
                        data-size="<?= $size; ?>">

                        <div class="avcd-macronutrients-stats stats-score">
                            <?php
                            $statusClass = '';
                            switch (strtolower($analysis['carbs']['status'])) {
                                case strtolower(NutrientEnum::IN_RANGE):
                                    $statusClass = 'success';
                                    break;

                                case strtolower(NutrientEnum::LOW):
                                case strtolower(NutrientEnum::VERY_LOW):
                                    $statusClass = 'warning';
                                    break;

                                case strtolower(NutrientEnum::HIGH):
                                case strtolower(NutrientEnum::VERY_HIGH):
                                    $statusClass = 'danger';
                                    break;

                                default:
                                    $statusClass = 'default';
                                    break;
                            }
                            ?>
                            <p class="avcd-percentage <?= $statusClass; ?>">
                                <?= round($analysis['carbs']['percentage'] * 100); ?>%<br>
                            </p>
                        </div>
                    </div>
                </div>

                <h2>GLUCIDES</h2>
            </div>
        </div>

        <div class="avcd-cart-macronutrient-mini avcd-modal-content-third">
            <div class="avcd-cart-macronutrient-content">
                <div class="avcd-box-pie">
                    <div id="avcd-box-pie-proteins-wrapper"
                        class="avcd-box-pie-wrapper avcd-box-pie-proteins-wrapper <?= $size; ?>"
                        data-nutrient="proteins"
                        data-percent="[<?= $analysis['proteins']['percentage']; ?>]"
                        data-min="<?= $analysis['proteins']['range']['min']; ?>"
                        data-max="<?= $analysis['proteins']['range']['max']; ?>"
                        data-size="<?= $size; ?>">

                        <div class="avcd-macronutrients-stats stats-score">
                            <?php
                            $statusClass = '';
                            switch (strtolower($analysis['proteins']['status'])) {
                                case strtolower(NutrientEnum::IN_RANGE):
                                    $statusClass = 'success';
                                    break;

                                case strtolower(NutrientEnum::LOW):
                                case strtolower(NutrientEnum::VERY_LOW):
                                    $statusClass = 'warning';
                                    break;

                                case strtolower(NutrientEnum::HIGH):
                                case strtolower(NutrientEnum::VERY_HIGH):
                                    $statusClass = 'danger';
                                    break;

                                default:
                                    $statusClass = 'default';
                                    break;
                            }
                            ?>
                            <p class="avcd-percentage <?= $statusClass; ?>">
                                <?= round($analysis['proteins']['percentage'] * 100); ?>%<br>
                            </p>
                        </div>
                    </div>
                </div>

                <h2>PROTEINES</h2>
            </div>
        </div>

        <div class="avcd-cart-macronutrient-mini avcd-modal-content-third">
            <div class="avcd-cart-macronutrient-content">
                <div class="avcd-box-pie">
                    <div id="avcd-box-pie-fats-wrapper"
                        class="avcd-box-pie-wrapper avcd-box-pie-fats-wrapper <?= $size; ?>"
                        data-nutrient="fat"
                        data-percent="[<?= $analysis['fat']['percentage']; ?>]"
                        data-min="<?= $analysis['fat']['range']['min']; ?>"
                        data-max="<?= $analysis['fat']['range']['max']; ?>"
                        data-size="<?= $size; ?>">

                        <div class="avcd-macronutrients-stats stats-score">
                            <?php
                            $statusClass = '';
                            switch (strtolower($analysis['fat']['status'])) {
                                case strtolower(NutrientEnum::IN_RANGE):
                                    $statusClass = 'success';
                                    break;

                                case strtolower(NutrientEnum::LOW):
                                case strtolower(NutrientEnum::VERY_LOW):
                                    $statusClass = 'warning';
                                    break;

                                case strtolower(NutrientEnum::HIGH):
                                case strtolower(NutrientEnum::VERY_HIGH):
                                    $statusClass = 'danger';
                                    break;

                                default:
                                    $statusClass = 'default';
                                    break;
                            }
                            ?>
                            <p class="avcd-percentage <?= $statusClass; ?>">
                                <?= round($analysis['fat']['percentage'] * 100); ?>%<br>
                            </p>
                        </div>
                    </div>
                </div>

                <h2>ACIDES GRAS</h2>
            </div>
        </div> <!-- .avcd-cart-macronutrient-mini -->
    </div>

    <div class="avcd-cart-micronutrient-mini">
        <table>
            <?php
            foreach ($analysis as $slug => $data) :
                if (!in_array($slug, [
                    'vitamin_a',
                    'vitamin_b',
                    'vitamin_c',
                    'iron',
                    'calcium',
                    'fiber',
                    'sodium',
                    'sugars',
                    'cholesterol',
                ])) :
                    continue;
                endif;

                $statusClass = '';
                switch (strtolower($analysis['fat']['status'])) {
                    case strtolower(NutrientEnum::IN_RANGE):
                        $statusClass = 'success';
                        break;

                    case strtolower(NutrientEnum::LOW):
                    case strtolower(NutrientEnum::VERY_LOW):
                        $statusClass = 'warning';
                        break;

                    case strtolower(NutrientEnum::HIGH):
                    case strtolower(NutrientEnum::VERY_HIGH):
                        $statusClass = 'danger';
                        break;

                    default:
                        $statusClass = 'default';
                        break;
                }

                $percent = (int) round($data['percentage'] * 100);

                if ($percent >= 100) :
                    $progress = 100;
                else :
                    $firstDigit = substr($percent, 0, 1);
                    $secondDigit = substr($percent, 1, 1);

                    if ((int) $secondDigit < 5) :
                        $progress = $firstDigit . 0;
                    else :
                        $progress = $firstDigit . 5;
                    endif;
                endif;
                ?>
                <tr>
                    <td class="label-micronutrient"><?= mb_strtoupper(NutrientEnum::getNameFromSlug($slug, $locale)); ?></td>
                    <td class="percent-progress">
                        <div class="progress <?= $statusClass; ?>">
                            <div class="bar width-<?= (int) $progress; ?>"></div>
                        </div>
                    </td>
                    <td class="percent-string"><?= $percent; ?>%</td>
                </tr>
            <?php
            endforeach;
            ?>
        </table>

    </div> <!-- .avcd-cart-micronutrient-mini -->

</div> <!-- .chart-container -->

<?php if ($addCssJs) : ?>
    <script type='text/javascript'>
        pie.draw('.avcd-box-pie-wrapper');
    </script>
<?php endif; ?>
