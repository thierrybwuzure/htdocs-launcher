<?php /* Bouton clair / sombre : un disque dont une moitié est pleine ; il fait un demi-tour à chaque clic. */ ?>
<button type="button" id="theme" class="theme" aria-label="Changer de thème" title="Changer de thème">
    <svg class="theme-icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
        <g class="theme-half">
            <circle cx="16" cy="16" r="11" fill="none" stroke="currentColor" stroke-width="2.4"/>
            <path d="M16 5 A11 11 0 0 0 16 27 Z" fill="currentColor"/>
        </g>
    </svg>
</button>
