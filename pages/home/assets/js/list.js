const categorias = [
    "E-books",
    "Finaças",
    "Saude",
    "Bem-Estar",
    "Alimentacao"
]

let container_cards = document.getElementById("container_cards")
let filtro = document.getElementById("filtro")

categorias.forEach(elemento_categoria => {
    filtro.innerHTML += `<option value="${elemento_categoria}">${elemento_categoria}</option>`
});

function limpa_elemento(identificador) {
    document.getElementById(identificador).innerHTML = "";
}
function gera_cards(id, titulo, foto, preco) {

    return `
            
            <a href="?get_product=${id}">
                <div class="card">
                    <div class="img"  style="background-image: url(${foto});"></div>
                        <div class="descricao_card">
                            <h4 class="nome">${titulo}</h4>
                        
                            <div class="linha">
                                <p class="localizacao">Preço: </p>
                                <p class="preco"> AOA ${preco}</p>
                                
                            </div>

                        </div>
                    
                </div>
            </a>
        `


     
}

