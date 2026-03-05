<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px;'>Будь ласка, оберіть підприємство у списку зверху.</div>";
    exit;
}

$sql = "SELECT question, content_type, content_data FROM REF_POPULAR_QUESTIONS WHERE is_active = 1 ORDER BY sort_order ASC";
$res = mysqli_query($link, $sql);

// Використовуємо клас .tree-icon, для якого в cabinet_ent.css уже прописані стилі
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon" width="16" height="16" style="pointer-events: none;">';
?>

<link href="/css/Popular_Questions.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header">
    <h3 style="margin: 0;">Поширені запитання</h3>
</div>

<div style="padding: 15px 0;">
    <?php if ($res && mysqli_num_rows($res) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($res)): 
            
            // Якщо тип контенту - HTML, нам НЕ треба його парсити як JSON
            if ($row['content_type'] === 'html') {
                $itemData = $row['content_data']; // Просто беремо HTML як є
            } else {
                // Для старих питань намагаємося розшифрувати JSON
                $itemData = json_decode($row['content_data'], true);
            
                // Перевірка на помилки тільки для JSON-блоків
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo "<div style='color:red; padding:10px 20px;'>Помилка JSON у питанні '{$row['question']}': " . json_last_error_msg() . "</div>";
                    continue; // Пропускаємо малювання, якщо JSON зламаний
                }
            }
        ?>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div style="margin-right: 10px;"><?php echo $caret_icon; ?></div>
                    <?php echo htmlspecialchars($row['question']); ?>
                </div>
                
                <div class="faq-answer">
                    <?php if ($row['content_type'] === 'text'): ?>
                        <p><?php echo $itemData; ?></p>
                    
                    <?php elseif ($row['content_type'] === 'html'): ?>
                        <div class="quill-content">
                            <?php echo $row['content_data']; ?>    
                        </div>
                    <?php elseif ($row['content_type'] === 'file'): ?>
                        <p><?php echo htmlspecialchars($itemData['description'] ?? ''); ?></p>
                        <a href="<?php echo htmlspecialchars($itemData['file_url']); ?>" class="faq-file-link" download>
                            <img src="/img/attach.svg" width="16" style="margin-right: 8px;"> 
                            <?php echo htmlspecialchars($itemData['file_name']); ?>
                        </a>
                        
                    <?php elseif ($row['content_type'] === 'links'): ?>
                        <ul class="faq-links-list">
                            <?php foreach ($itemData as $link): ?>
                                <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank">🔗 <?php echo htmlspecialchars($link['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        
                    <?php elseif ($row['content_type'] === 'structure'): ?>
                        <ul style="padding-left: 20px;">
                            <?php foreach ($itemData as $li): ?>
                                <li><?php echo htmlspecialchars($li); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                    <?php elseif ($row['content_type'] === 'advanced_structure'): ?>
                        <?php foreach ($itemData['blocks'] as $block): ?>
                            
                            <?php if ($block['type'] === 'paragraph'): ?>
                                <p style="margin-bottom: 10px; font-weight: bold;">
                                    <?php echo htmlspecialchars($block['text']); ?>
                                </p>
                            
                            <?php elseif ($block['type'] === 'paragraph_with_link'): ?>
                                <p style="margin-bottom: 10px; line-height: 1.6;">
                                    <?php echo htmlspecialchars($block['text_before'] ?? ''); ?>
                                    
                                    <?php if (!empty($block['link'])): ?>
                                        <a href="<?php echo htmlspecialchars($block['link']['url']); ?>" target="_blank" style="color: #3C9ADC; text-decoration: underline;">
                                            <?php echo htmlspecialchars($block['link']['text']); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php echo htmlspecialchars($block['text_after'] ?? ''); ?>
                                </p>
                                
                            <?php elseif ($block['type'] === 'ordered_list'): ?>
                                <ol style="padding-left: 20px; line-height: 1.6;">
                                    <?php foreach ($block['items'] as $li): ?>
                                        <li style="margin-bottom: 15px;">
                                            <strong><?php echo htmlspecialchars($li['title']); ?></strong><br>
                                            
                                            <?php if (!empty($li['text'])): ?>
                                                <?php echo htmlspecialchars($li['text']); ?>
                                            <?php endif; ?>

                                            <?php if (!empty($li['external_link'])): ?>
                                                <a href="<?php echo htmlspecialchars($li['external_link']['url']); ?>" target="_blank" style="color: #3C9ADC; font-weight: bold; text-decoration: underline;">
                                                    <?php echo htmlspecialchars($li['external_link']['label']); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($li['link'])): ?>
                                                <a href="#" onclick="document.querySelectorAll('.sidebar a').forEach(a => { if(a.innerText.trim() === '<?php echo htmlspecialchars($li['link']['target']); ?>') a.click(); }); return false;" style="color: #3C9ADC; font-weight: bold; text-decoration: underline;">
                                                    <?php echo htmlspecialchars($li['link']['label']); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($li['sublist'])): ?>
                                                <ul style="padding-left: 20px; list-style-type: disc; margin-top: 5px;">
                                                    <?php foreach ($li['sublist'] as $sub): ?>
                                                        <li><?php echo htmlspecialchars($sub); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>

                                            <?php if (!empty($li['text_after'])): ?>
                                                <div style="margin-top: 5px;">
                                                    <?php echo htmlspecialchars($li['text_after']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                            
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
