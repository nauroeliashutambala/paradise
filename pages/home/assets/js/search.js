let search_input = document.getElementById("search_input");

search_input.addEventListener("keydown", () => {
    const value_to_search = search_input.value;

    if (!value_to_search == "") {
        pesquisa(value_to_search);
    }else{
        console.log("Erro ao pesquisar " + value_to_search);
    }
})


function pesquisa(texto) {
    limpa_elemento("container_cards")
    //Aqui fazer o fetch do dado
    pesquisar_Produtos(texto)
}
function add_card(card) {
    container_cards.innerHTML += `<div class="list">
            ${card}
        </div>
        `
}
function add_element_to(elemento, to_destain) {
    to_destain.innerHTML += elemento
}