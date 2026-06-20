import http from 'http';
const server = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'text/plain' });
  res.end('ok');
});
server.listen(9000, () => {
  console.log('Listening on 9000...');
});
// Prevent immediate exit
setInterval(() => {
  console.log('Heartbeat...');
}, 1000);
