<?php

/**
 * Adiciona mensagem explicativa no formulário de login
 */
function museusbr_the_login_message( $message ) {
    return $message . '<div class="museusbr-login-message">
        <p>
            Estamos de cara nova e trabalhando para melhorar ainda mais a experiência dos usuários!
            Neste momento, não estamos aceitando novos cadastros de museus. O IBRAM abrirá uma campanha de recadastramento ainda no primeiro semestre deste ano e fará uma chamada pública para novos cadastros, atualizações de informação e demais ações para potencialização do Cadastro Nacional de Museus.</a>
        </p>
        <p>Em caso de dúvidas, não hesite em entrar em contato com nossa equipe:</p>
        <ul>
            <li><a href="mailto:cnm@museus.gov.br">cnm@museus.gov.br</a></li>
            <li><a href="mailto:registro@museus.gov.br">registro@museus.gov.br</a></li>
            <li><a href="tel:+556135214291">(61) 3521-4291</a></li>
            <li><a href="tel:+556135214329">(61) 3521-4329</a></li>
            <li><a href="tel:+556135214334">(61) 3521-4334</a></li>
            <li><a href="tel:+556135214410">(61) 3521-4410</a></li>
            <li><a href="tel:+556135214294">(61) 3521-4294</a></li>
        </ul>
    </div>';
}
add_filter( 'login_message', 'museusbr_the_login_message' );

/**
 * Troca o rótulo do link de "Perdeu a senha?" para "Recuperar senha"
 */
function museusbr_br_password_reset_text( $html_link )   {

    $link_text_default = 'Perdeu a senha?';
    $link_text_new = 'Recuperar senha';

    $html_link = str_replace($link_text_default, $link_text_new, $html_link);

    return $html_link;

}
add_filter( 'lost_password_html_link', 'museusbr_br_password_reset_text' );