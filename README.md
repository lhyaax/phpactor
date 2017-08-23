# phpactor
PHP refactoring and introspection tool. fork form phpactor/phpactor


### and to .vimrc

```
autocmd FileType php setlocal omnifunc=phpactor#Complete
nnoremap <silent><unique><C-]> :call phpactor#GotoDefinition()<CR>
nnoremap <silent><unique><C-t> :call phpactor#JumpBack()<CR>
```
