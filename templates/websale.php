<div class="row">
    <div class="span4 offset4">
        <?php if(isset($flash['websale_error'])): ?>
            <div class="alert alert-error"><?php echo $flash['websale_error'] ?></div>
        <?php endif ?>
            <h1>Bienvenue</h1>

            <p>Tu vas réaliser un paiement à <em><?php echo $fundation ?></em> via payutc.</p> 
            
            <form action="websale" method="post">
                <input type="hidden" name="tra_id" value="<?php echo $_GET['tra_id'] ?>" />
                <input type="hidden" name="token" value="<?php echo $_GET['token'] ?>" />
                <input type="hidden" name="method" value="direct" />
                <table class="table table-hover" id="opTable">
                    <tbody>
                        <?php foreach($purchases as $purchase): ?>
                        <tr>
                            <td><?php echo $purchase->pur_qte ?> <?php echo $products[$purchase->obj_id]->name ?> à <?php echo format_amount($purchase->pur_unit_price) ?> €</td>
                            <td class="debit"> - <?php echo format_amount($purchase->pur_price) ?> €</td>
                        </tr>
                        <?php endforeach ?>
                        <tr>
                            <td>
                                <b>Total à payer</b>
                            </td>
                            <td class="debit">- <?php echo format_amount($total) ?> €</td>
                        </tr>
                    </tbody>
                </table>

                <div id="boutons1" class="center">
                    <p><a href="login" class="btn btn-primary" title="Connexion à payutc" />J'ai un compte payutc</a></p>
                    <p><a href="#" class="btn btn-primary" title="Je n'ai pas de compte payutc" id="noaccount"/>Je n'ai pas de compte payutc</a></p>
                </div>
                <div id="boutons2">
                    <p>
                        <label class="checkbox">
                            <input type="checkbox" name="cgu" id="cgu"> J'accepte les CGU de payutc
                        </label>
                    </p>
                    <p><input type="submit" class="btn btn-primary" value="Payer par CB" id="gopay" /></p>
                </div>
            </form> 
      </div>
</div>