const fs = require('fs');
const path = 'C:/xampp/htdocs/4nsolarSystem/quotations.php';
const text = fs.readFileSync(path, 'utf8');
const pattern = /<\?php elseif \(\$action == 'installments' && isset\(\$quote\)\): \?>[\s\S]*?(?=<\?php elseif \(\$action == 'installments' && isset\(\$quote\)\): \?>)/;
if (!pattern.test(text)) {
  throw new Error('pattern not found');
}
const newText = text.replace(pattern, '');
fs.writeFileSync(path, newText);
