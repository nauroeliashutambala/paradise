function add_itens_menu(conteudo, img, url) {
    document.querySelector(".menu").innerHTML += `
        <a href="${url}" class="item">
            <img src="${img}" alt="">
            <div>
                ${conteudo}
            </div>
        </a>
    `
}
function add_itens_nav_menu(conteudo, url) {
    document.querySelector(".lista_menu").innerHTML += `
        <li><a href="${url}">${conteudo}</a></li>
    `
}

add_itens_nav_menu("Inicio", "url")
add_itens_nav_menu("Reservas", "url")
add_itens_nav_menu("Pendentes", "url")
add_itens_nav_menu("Login", "url")


add_itens_menu("Inicio", "img", "url")
add_itens_menu("E-books", "img", "url")
add_itens_menu("Vender", "img", "url")
add_itens_menu("Painel", "img", "url")

function abrir_menu() {
    document.querySelector(".menu").classList.toggle("open")
}

abrir_menu()