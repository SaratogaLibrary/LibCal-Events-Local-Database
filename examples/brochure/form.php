<?php
    require_once('../../config.php');

    // For getting the available audiences, categories, and locations    
    $cat_url = 'examples/json-providers/categories.php';
    $aud_url = 'examples/json-providers/audiences.php';
    $loc_url = 'examples/json-providers/locations.php';
    $categories = json_decode(@file_get_contents(CONTENT_URL . $cat_url));
    $audiences  = json_decode(@file_get_contents(CONTENT_URL . $aud_url));
    $locations  = json_decode(@file_get_contents(CONTENT_URL . $loc_url));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brochure Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
    <h1>Custom Event Brochure Creator</h1>
    <p>This generates a PDF of the currently scheduled upcoming programs (more are added all the time!) based on the selections you choose.</p>

    <form action="index.php" class="container-fluid">
        <div class="mb-3">
            <div class="row">
                <div class="col-6">
                    <label for="start" class="form-label">Start date:</label>
                    <input type="date" class="form-control" id="start" name="event-search-start" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime("+{$total_days} days")) ?>" />
                </div>
                <div class="col-6">
                    <label for="end" class="form-label">End date:</label>
                    <input type="date" class="form-control" id="end" name="event-search-end" value="<?= date('Y-m-d', strtotime('+6 days')) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime("+{$total_days} days")) ?>" />
                </div>
                <div id="passwordHelpBlock" class="form-text">
                    The <strong>maximum</strong> end date can only be <?= $total_days ?> days from today, regardless of what is chosen.<br>
                    If no appropriate values are chosen, the current week will be used.
                </div>
            </div>
        </div>
            
        <div class="mb-3">
            <div class="row">
                <div class="col-3">
                    <label for="audiences" class="form-label">Audiences</label>
                    <select name="audiences" id="audiences" class="form-select" aria-label="Select" multiple="multiple" size="4">
<?php foreach ($audiences as $audience_id => $audience): ?>
                        <option value="<?= $audience_id ?>"><?= $audience ?></option>
<?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label for="categories" class="form-label">Categories</label>
                    <select name="categories" id="categories" class="form-select" aria-label="Select" multiple="multiple" size="4">
<?php foreach ($categories as $category_id => $category): ?>
                        <option value="<?= $category_id ?>"><?= $category ?></option>
<?php endforeach; ?>
                    </select>
                </div>
                <div class="col-3">
                    <label for="locations" class="form-label">Locations</label>
                    <select name="locations" id="locations" class="form-select" aria-label="Select" multiple="multiple" size="4">
<?php foreach ($locations as $location_id => $location): ?>
                        <option value="<?= $location_id ?>"><?= $location ?></option>
<?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <input class="btn btn-primary" type="submit" value="Generate Brochure!" />
            <input class="btn btn-secondary" type="reset" value="Reset Values" />
        </div>
    </form>
</body>
</html>