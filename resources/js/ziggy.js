const Ziggy = {"url":"http:\/\/localhost","port":null,"defaults":{},"routes":{"subprodutos.index":{"uri":"subprodutos","methods":["GET","HEAD"]},"subprodutos.create":{"uri":"subprodutos\/create","methods":["GET","HEAD"]},"subprodutos.store":{"uri":"subprodutos","methods":["POST"]},"subprodutos.fetch":{"uri":"subprodutos\/fetch\/{subproduto}","methods":["GET","HEAD"],"parameters":["subproduto"]},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };