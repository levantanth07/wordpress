<?php
/** @var string|null $message */
/** @var string|null $errors */
/** @var string $stubUrl */
?>

<h1>Import sản phẩm</h1>
<p><?= $message ?></p>
<ul class="errors">
    <?php
    foreach ($errors as $index => $error) {
        printf('<li>Dòng %d<ul class="error-item">', $index,);
        foreach ($error as $item) {
            printf('<li>%s</li>', $item);
        }
        printf('</ul></li>');
    }
    ?>
</ul>
<form action="<?= admin_url('admin.php?page=import-products&amp;merchant_id=' . $_GET['merchant_id']) ?>" method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <td>
                File mẫu
            </td>
            <td>
                <a href="<?= esc_url($stubUrl) ?>">Download</a>
            </td>
        </tr>

        <tr>
            <td>
                File import
            </td>
            <td>
                <input type="file" name="file" />
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <button type="submit">Import sản phẩm</button>
            </td>
        </tr>
    </table>
</form>

<style>
    ul.errors {
        list-style-type: upper-roman;
    }

    ul.error-item {
        list-style-type: square;
    }
</style>
