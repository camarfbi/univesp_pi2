$(document).on('click', '.navItem', function(e) {
    e.preventDefault();  // Impede o comportamento padrão do link
    const pageLink = $(this).data('link');  // Obtém o valor de data-link

    if (pageLink && pageLink !== '#') {
        // Carrega a página no espaço designado
        loadPage(pageLink);
    } else {
        console.error("Link da página não encontrado ou inválido.");
    }
});


function loadPage(page) {
    const contentDiv = document.querySelector('.space-y-5');
    const fullPath = getFullPath(page);  // Obtenha o caminho completo

    fetch(fullPath)
        .then(response => {
            if (!response.ok) {
                console.error('Página não encontrada:', fullPath);
                return fetch(getFullPath('erro.php')).then(errorResponse => errorResponse.text());
            }
            return response.text();
        })
        .then(data => {
            contentDiv.innerHTML = data;
            localStorage.setItem('currentPage', page); // Salva a página atual no localStorage

            reinitializeScripts();  // Reinicializa os scripts após carregar a página

            // Verifica se a página carregada exige um script específico e o inclui
            loadSpecificScript(page);
        })
        .catch(error => {
            console.error('Erro ao carregar a página:', error);
            fetch(getFullPath('erro.php'))
                .then(response => response.text())
                .then(data => {
                    contentDiv.innerHTML = data;
                    reinitializeScripts();
                });
        });

    console.log('Carregando a página:', fullPath);
}

function getFullPath(page) {
    const adminPages = ['list-users.php', 'users.php', 'edit-user.php'];
    const associadosPages = ['list-associados.php', 'associado.php'];

    if (adminPages.includes(page)) {
        return 'admin/' + page;
    }

    if (associadosPages.includes(page)) {
        return 'associados/' + page;
    }

    return page;  // Se estiver na raiz
}

function reinitializeScripts() {
    const currentPage = localStorage.getItem('currentPage');
	console.log('Página atual armazenada no localStorage:', currentPage);  // Para verificar o valor atual

    if (typeof loadData === 'function') {
        loadData($('#search').val(), 1, $('#recordsPerPage').val(), currentPage);
    }

    $('#search').off('keyup').on('keyup', function() {
        const searchTerm = $(this).val();
        loadData(searchTerm, 1, $('#recordsPerPage').val(), currentPage);
    });

    $('#recordsPerPage').off('change').on('change', function() {
        const recordsPerPage = $(this).val();
        loadData($('#search').val(), 1, recordsPerPage, currentPage);
    });

    $(document).off('click', '.pagination-link').on('click', '.pagination-link', function(e) {
        e.preventDefault();
        const page = $(this).attr('href').split('page=')[1].split('&')[0];
        loadData($('#search').val(), page, $('#recordsPerPage').val(), currentPage);
    });
}

// Função para carregar o script JavaScript específico de cada página
function loadSpecificScript(page) {
    let scriptSrc = '';

    // Verifique qual página foi carregada e atribua o script correto
    if (page.includes('list-users')) {
        scriptSrc = 'assets/js/users.js';  // Caminho para o script de usuários
    } else if (page.includes('list-associados')) {
        scriptSrc = 'assets/js/associados.js';  // Caminho para o script de associados
    }

    // Se um script foi identificado, carregue-o dinamicamente
    if (scriptSrc) {
        const script = document.createElement('script');
        script.src = scriptSrc;
        script.type = 'text/javascript';
        document.body.appendChild(script);
        console.log('Carregado script:', scriptSrc);
    } else {
        console.warn('Nenhum script específico para esta página.');
    }
}

// Função para carregar os dados dos usuários e associados e renderizar suas respectivas tabelas
function loadData(searchTerm = '', page = 1, recordsPerPage = 10) {
    const currentPage = localStorage.getItem('currentPage');
    console.log('Página atual:', currentPage);  // Verificar se a página correta está sendo identificada

    if (!currentPage) {
        console.error('currentPage não está definido no localStorage.');
        return;
    }

    const fullPath = getFullPath(currentPage);
    console.log('Caminho completo:', fullPath);  // Verificar se o caminho correto está sendo gerado

    if (currentPage.includes('list-users')) {
        console.log('Entrou no bloco list-users');  // Verificação
        $.ajax({
            url: fullPath,
            type: 'GET',
            data: {
                search: searchTerm,
                ajax: 1,
                recordsPerPage: recordsPerPage,
                page: page
            },
            dataType: 'json',
            success: function(response) {
                const userTableBody = $('#user-table-body');
                userTableBody.empty();

                if (response.data.length === 0) {
                    userTableBody.append(`
                        <tr class="odd">
                            <td colspan="6" class="table-td">
                                <div>
                                    <span style="text-transform: none;">Nenhum resultado</span>
                                </div>
                            </td>
                        </tr>
                    `);
                } else {
                    response.data.forEach(function(user) {
                        userTableBody.append(renderUserRow(user));
                    });
                }

                $('#pagination').html(response.pagination);
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    } else if (currentPage.includes('list-associados')) {
        console.log('Entrou no bloco list-associados');  // Verificação
        $.ajax({
            url: fullPath,
            type: 'GET',
            data: {
                search: searchTerm,
                ajax: 1,
                recordsPerPage: recordsPerPage,
                page: page
            },
            dataType: 'json',
            success: function(response) {
                const associadoTableBody = $('#associado-table-body');
                associadoTableBody.empty();

                if (response.data.length === 0) {
                    associadoTableBody.append(`
                        <tr class="odd">
                            <td colspan="6" class="table-td">
                                <div style="text-transform: none; text-align: center;">
                                    <span style="text-transform: none; text-align: center;">Nenhum resultado</span>
                                </div>
                            </td>
                        </tr>
                    `);
                } else {
                    response.data.forEach(function(associado) {
                        associadoTableBody.append(renderAssociadoRow(associado));
                    });
                }

                $('#pagination').html(response.pagination);
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    } else {
        console.error('Página não identificada corretamente. Valor de currentPage:', currentPage);
    }
}
