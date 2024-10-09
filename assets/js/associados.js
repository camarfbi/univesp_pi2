// Função responsável por renderizar uma linha da tabela de associados
function renderAssociadoRow(associado) {
    const statusClass = associado.status == 'inativo' ? 'text-danger-500 bg-danger-500' : 'text-success-500 bg-success-500';
    const statusText = associado.status == 'inativo' ? 'Inativo' : 'Ativo';

    return `
        <tr class='odd'>
            <td class='table-td sorting_1'>${associado.id}</td>
            <td class="table-td">
                <span class="flex">
                    <span class="w-7 h-7 rounded-full ltr:mr-3 rtl:ml-3 flex-none">
                        <img src="assets/images/all-img/customer_1.png" alt="1" class="object-cover w-full h-full rounded-full">
                    </span>
                    <span class="text-sm text-slate-600 dark:text-slate-300 capitalize">${associado.nome} ${associado.sobrenome}</span>
                </span>
            </td>
            <td class="table-td"><span style="text-transform: none;">${associado.email}</span></td>
            <td class="table-td">
                <div><span style="text-transform: none;">${associado.associado}</span></div>
            </td>
            <td>
                <div class='inline-block px-3 min-w-[90px] text-center mx-auto py-1 rounded-[999px] bg-opacity-25 ${statusClass}'>${statusText}</div>
            </td>
            <td>
                <div class="flex space-x-3 rtl:space-x-reverse">
                    <button class="action-btn" type="button">
                        <iconify-icon icon="heroicons:eye"></iconify-icon>
                    </button>
                    <a href="associado.php?id=${associado.id}" class="action-btn">
                        <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                    </a>
                    <button class="action-btn" type="button">
                        <iconify-icon icon="heroicons:trash"></iconify-icon>
                    </button>
                </div>
            </td>
        </tr>
    `;
}