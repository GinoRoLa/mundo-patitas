fetch('test_fetch.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ prueba: 'ok' })
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
