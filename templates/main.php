<div class="jumbotron">
    <h1>Bonjour, <?php echo $userDetails["firstname"] ?> <?php echo $userDetails["lastname"] ?> !</h1>
    <br />
    <p>
        Ton solde PayIcam est de <strong><?php echo format_amount($userDetails["credit"]) ?> €</strong><br>
        Ton solde d'event est de <strong><?php echo format_amount($userDetails["credit_event"]) ?> €</strong><br>
        Ton solde d'ecocups est de <strong><?php echo $userDetails["credit_ecocup"] ?> </strong><br>
        Ton solde d'ecocups de soirée est de <strong><?php echo $userDetails["credit_ecocup_soiree"] ?> </strong><br>
    </p>

</div>

<?php if(isset($flash['indice_gala'])): ?>
    <div class="alert alert-success"><?php echo $flash['indice_gala'] ?></div>
<?php endif ?>
<div class="row">
    <div class="col-md-4">
        <h2><a name="rechargement" rel="tooltip" data-placement="bottom" data-original-title="Recharger ton compte par Carte Bancaire" class="noul">Rechargement</a></h2>
        <?php if($canReload): ?>
            <?php if(isset($flash['reload_erreur'])): ?>
                <div class="alert alert-danger"><?php echo $flash['reload_erreur'] ?></div>
            <?php endif ?>
            <?php if(isset($flash['reload_ok'])): ?>
                <div class="alert alert-success"><?php echo $flash['reload_ok'] ?></div>
            <?php endif ?>
            <form action="reload" method="post" class="well form-inline" role="form">
                <div class="row">
                    <div class="col-xs-5">
                        <div class="input-group amount-container">
                            <?php
                            if(isset($flash['reload_value'])) {
                                $reload_value = $flash['reload_value'];
                            } else {
                                $reload_value = "";
                            }
                            ?>
                            <input name="montant" type="number" placeholder="0,00" class="form-control amount-selector" min="<?php echo $minReload/100?>" max="<?php echo $maxReload/100?>" value="<?php echo $reload_value ?>" step="0.01" />
                            <span class="input-group-addon">€</span>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <button type="submit" class="btn btn-primary "><i class="glyphicon glyphicon-shopping-cart glyphicon glyphicon-white"></i> Recharger</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-success">
                Ton compte ne peut être rechargé : <?php echo $cannotReloadMessage ?>
            </div>
        <?php endif ?>


        <!-- <h2><a name="rechargement_papercut" rel="tooltip" data-placement="bottom" data-original-title="Recharger ton compte par Carte Bancaire" class="noul">Créditer PaperCut</a></h2> -->
        <?php  /* if(!empty($canReloadPapercut)): ?>
            <?php if(isset($flash['reloadPaperCut_erreur'])): ?>
                <div class="alert alert-danger"><?php echo $flash['reloadPaperCut_erreur'] ?></div>
            <?php endif ?>
            <?php if(isset($flash['reloadPaperCut_ok'])): ?>
                <div class="alert alert-success"><?php echo $flash['reloadPaperCut_ok'] ?></div>
            <?php endif ?>
            <form action="reload_papercut" method="post" class="well form-inline" role="form">
                <p>
                    Somme rechargements PaperCut: <strong><?= $reloadsPapercut['amount'] ?>€</strong>
                    <?php if (!empty($reloadsPapercut['waiting'])): ?>
                        <br><small>Dont <?= $reloadsPapercut['waiting'] ?>€ en attente</small>
                    <?php endif ?>
                </p>
                <div class="row">
                    <div class="col-xs-5">
                        <div class="input-group amount-container">
                            <?php
                            if(isset($flash['reloadPaperCut_value'])) {
                                $reload_value = $flash['reloadPaperCut_value'];
                            } else {
                                $reload_value = "";
                            }
                            ?>
                            <input name="montant" type="number" placeholder="0,00" class="form-control amount-selector" max="<?php echo $userDetails["credit"]?>" value="<?php echo $reload_value ?>" step="0.05" />
                            <span class="input-group-addon">€</span>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <button type="submit" class="btn btn-primary "><i class="glyphicon glyphicon-shopping-cart glyphicon glyphicon-white"></i> Créditer</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-success">
                Ton compte ne peut être rechargé : <?php echo $cannotReloadPaperCutMessage ?>
            </div>
        <?php endif //*/ ?>

        <h2><a name="virement" rel="tooltip" data-placement="bottom" data-original-title="Transférer gratuitement de l'argent à un autre utilisateur de PayIcam" class="noul">Virement à un ami </a></h2>
        <?php if(isset($flash['virement_ok'])): ?>
            <div class="alert alert-success">
                <?php echo $flash['virement_ok'] ?>
            </div>
        <?php endif ?>
        <?php if(isset($flash['virement_erreur'])): ?>
            <div class="alert alert-danger">
                <?php echo $flash['virement_erreur'] ?>
            </div>
        <?php endif ?>
        <form action="virement" method="post" class="well form-horizontal">
            <div class="form-group">
                <div class="col-sm-10">
                    <input class="form-control" id="userName" name="userName" placeholder="Destinataire" type="text" autocomplete="off"/>
                    <input id="userId" name="userId" type="hidden" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-10">
                    <input class="form-control" name="message" placeholder="Message" type="text" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-5">
                    <div class="input-group amount-container">
                        <input name="montant" placeholder="0,00" type="number" class="form-control amount-selector" min="0" max="<?php echo $userDetails["credit"] ?>" step="0.01" />
                        <span class="input-group-addon">€</span>
                    </div>
                </div>
                <div class="col-sm-5">
                    <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-arrow-right glyphicon glyphicon-white"></i> Transférer</button>
                </div>
            </div>
        </form>
        <h2><a name="virement" rel="tooltip" data-placement="bottom" data-original-title="En cas de perte ou vol de ton badge, tu peux ici bloquer et débloquer son utilisation pour PayIcam" class="noul">Blocage badge</a></h2>
        <?php if(isset($flash['block_erreur'])): ?>
            <div class="alert alert-danger"><?php echo $flash['block_erreur'] ?></div>
        <?php endif ?>
        <div class="well">
            <p>
                État du compte :
                <?php if($isBlocked): ?>
                    <span class="label label-danger">Bloqué <i class="glyphicon glyphicon-remove glyphicon glyphicon-white"></i></span>
                <?php else: ?>
                <span class="label label-success">Débloqué <i class="glyphicon glyphicon-ok glyphicon glyphicon-white"></i></span>
            <?php endif ?>
        </p>
        <p>
            <?php if($isBlocked): ?>
                <a class="btn btn-success" href="unblock">Débloquer mon compte</a>
            <?php else: ?>
                <a class="btn btn-danger" href="block">Bloquer mon compte</a>
            <?php endif ?>
        </p>
    </div>
</div>
<div class="col-md-8" >
    <h2>Historique</h2>
    <div>
        <div>
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#payicam" aria-controls="home" role="tab" data-toggle="tab">Historique PayIcam</a></li>
                <li role="presentation"><a href="#event" aria-controls="profile" role="tab" data-toggle="tab">Historique Event</a></li>
            </ul>
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="payicam">
                    <table id='historique' class='table table-striped table-bordered'>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Quantité</th>
                                <th>Article</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historique as $elt): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/y H:i:s', strtotime($elt->date)) ?>
                                    </td>
                                    <td>
                                        <?php echo $elt->quantity ?>
                                    </td>
                                    <?php if($elt->type == "PURCHASE"): ?>
                                        <td>
                                            <?php echo $elt->name ?> <small><?php echo $elt->fun ?></small>
                                        </td>
                                        <td class="debit">
                                            - <?php echo format_amount($elt->amount) ?> €
                                        </td>
                                    <?php elseif($elt->type == "RECHARGE"): ?>
                                        <td>
                                            Rechargement
                                        </td>
                                        <td class="credit">
                                             + <?php echo format_amount($elt->amount) ?> €
                                        </td>
                                    <?php elseif($elt->type == "VIRIN"): ?>
                                        <td>
                                            Virement de <?php echo $elt->firstname ?> <?php echo $elt->lastname ?>
                                            <?php if(!empty($elt->name)): ?>
                                                 (<?php echo $elt->name ?>)
                                             <?php endif ?>
                                        </td>
                                        <td class="credit">
                                            + <?php echo format_amount($elt->amount)?> €
                                        </td>
                                    <?php elseif($elt->type == "VIROUT"): ?>
                                        <td>
                                            Virement à <?php echo $elt->firstname ?> <?php echo $elt->lastname ?>
                                            <?php if(!empty($elt->name)): ?>
                                                 (<?php echo $elt->name ?>)
                                             <?php endif ?>
                                         </td>
                                        <td class="debit">
                                            - <?php echo format_amount($elt->amount)?> €
                                        </td>
                                    <?php endif ?>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                    <div class="center"><ul id="paging" class="pagination"></ul></div>
                </div>
                <div role="tabpanel" class="tab-pane" id="event">
                    <table id='historique' class='table table-striped table-bordered'>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Quantité</th>
                                <th>Article</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historique_event as $elt): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/y H:i:s', strtotime($elt->date)) ?>
                                    </td>
                                    <td>
                                        <?php echo $elt->quantity ?>
                                    </td>
                                    <?php if($elt->type == "PURCHASE"): ?>
                                        <td>
                                            <?php echo $elt->name ?> <small><?php echo $elt->fun ?></small>
                                        </td>
                                        <td class="debit">
                                            - <?php echo format_amount($elt->amount) ?> €
                                        </td>
                                    <?php elseif($elt->type == "RECHARGE"): ?>
                                        <td>
                                            Rechargement
                                        </td>
                                        <td class="credit">
                                             + <?php echo format_amount($elt->amount) ?> €
                                        </td>
                                    <?php elseif($elt->type == "VIRIN"): ?>
                                        <td>
                                            Virement de <?php echo $elt->firstname ?> <?php echo $elt->lastname ?>
                                            <?php if(!empty($elt->name)): ?>
                                                 (<?php echo $elt->name ?>)
                                             <?php endif ?>
                                        </td>
                                        <td class="credit">
                                            + <?php echo format_amount($elt->amount)?> €
                                        </td>
                                    <?php elseif($elt->type == "VIROUT"): ?>
                                        <td>
                                            Virement à <?php echo $elt->firstname ?> <?php echo $elt->lastname ?>
                                            <?php if(!empty($elt->name)): ?>
                                                 (<?php echo $elt->name ?>)
                                             <?php endif ?>
                                         </td>
                                        <td class="debit">
                                            - <?php echo format_amount($elt->amount)?> €
                                        </td>
                                    <?php endif ?>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                    <div class="center"><ul id="paging_event" class="pagination"></ul></div>
                </div>
            </div>
        </div>


    </div>
</div>
<script type="text/javascript">
$(document).ready(function(){
    init();
    selectPage(1);
    $("a").tooltip();
});
</script>
