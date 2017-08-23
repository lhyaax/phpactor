
fork form phpactor/phpactor.


and to .vimrc

autocmd FileType php setlocal omnifunc=phpactor#Complete
nnoremap <silent><unique><buffer><C-]> :call phpactor#GoToDefinition()<CR>
nnoremap <silent><unique><C-t> :call phpactor#JumpBack()<CR>
