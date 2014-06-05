<div class="text-center">
    <ul class="pagination">
        <?php foreach($pages as $page): ?>
        <li<?php if($page['active']):?> class="active"<?php endif;?>>
            <a href="<?=$page['url'];?>"><?=$page['content'];?></a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
