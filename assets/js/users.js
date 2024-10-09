// Função responsável por renderizar uma linha da tabela de usuários
function renderUserRow(user) {
    const statusClass = user.bloqueado == '1' ? 'text-danger-500 bg-danger-500' : 'text-success-500 bg-success-500';
    const statusText = user.bloqueado == '1' ? 'Bloqueado' : 'Habilitado';

    return `
        <tr class='odd'>
            <td class='table-td sorting_1'>${user.id}</td>
            <td class="table-td">
                <span class="flex">
                    <span class="w-7 h-7 rounded-full ltr:mr-3 rtl:ml-3 flex-none">
                        <img src="assets/images/all-img/customer_1.png" alt="1" class="object-cover w-full h-full rounded-full">
                    </span>
                    <span class="text-sm text-slate-600 dark:text-slate-300 capitalize">${user.nome} ${user.sobrenome}</span>
                </span>
            </td>
            <td class="table-td"><span style="text-transform: none;">${user.email}</span></td>
            <td class="table-td">
                <div><span style="text-transform: none;">${user.user}</span></div>
            </td>
            <td>
                <div class='inline-block px-3 min-w-[90px] text-center mx-auto py-1 rounded-[999px] bg-opacity-25 ${statusClass}'>${statusText}</div>
            </td>
            <td>
                <div class="flex space-x-3 rtl:space-x-reverse">
                    <button class="action-btn" type="button">
                        <iconify-icon icon="heroicons:eye"></iconify-icon>
                    </button>
                    <a href="javascript:void(0)" class="navItem" data-link="../admin/users.php?id=${user.id}">
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
